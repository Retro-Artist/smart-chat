<?php
// src/Api/WhatsApp/InstanceManager.php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../Core/Logger.php';
require_once __DIR__ . '/../../Core/Redis.php';
require_once __DIR__ . '/../../Core/MessageQueue.php';
require_once __DIR__ . '/../Models/WhatsAppInstance.php';
require_once __DIR__ . '/../Models/WhatsAppSyncLog.php';
require_once __DIR__ . '/EvolutionAPI.php';

class InstanceManager {
    private $evolutionAPI;
    private $instanceModel;
    private $syncLogModel;
    private $redis;
    private $queue;
    
    public function __construct() {
        $this->evolutionAPI = new EvolutionAPI();
        $this->instanceModel = new WhatsAppInstance();
        $this->syncLogModel = new WhatsAppSyncLog();
        $this->redis = Redis::getInstance();
        $this->queue = new MessageQueue();
    }
    
    public function createInstance($userId, $phoneNumber = null) {
        try {
            $instanceName = $this->instanceModel->generateInstanceName($userId);
            $webhookUrl = WHATSAPP_WEBHOOK_URL;
            
            Logger::getInstance()->info("Creating WhatsApp instance", [
                'user_id' => $userId,
                'instance_name' => $instanceName,
                'phone_number' => $phoneNumber
            ]);
            
            $instance = $this->instanceModel->create($userId, $instanceName, null, $phoneNumber);
            
            $response = $this->evolutionAPI->createInstance($instanceName, $phoneNumber, $webhookUrl);
            
            if (isset($response['instance'])) {
                $evolutionInstance = $response['instance'];
                $updateData = [
                    'instance_id' => $evolutionInstance['instanceId'] ?? null,
                    'status' => $this->mapEvolutionStatus($evolutionInstance['status'] ?? 'creating'),
                    'webhook_url' => $webhookUrl
                ];
                
                if (isset($response['qrcode']['base64'])) {
                    $updateData['qr_code'] = $response['qrcode']['base64'];
                }
                
                $this->instanceModel->update($instance['id'], $updateData);
                
                $this->redis->set("whatsapp:instance:{$instance['id']}", $response, CACHE_TTL_INSTANCES);
                
                Logger::getInstance()->info("WhatsApp instance created successfully", [
                    'instance_id' => $instance['id'],
                    'evolution_instance_id' => $evolutionInstance['instanceId'] ?? null
                ]);
                
                return $this->instanceModel->findById($instance['id']);
            } else {
                $this->instanceModel->updateStatus($instance['id'], WhatsAppInstance::STATUS_FAILED);
                throw new Exception('Failed to create Evolution API instance: ' . json_encode($response));
            }
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to create WhatsApp instance: " . $e->getMessage());
            if (isset($instance['id'])) {
                $this->instanceModel->updateStatus($instance['id'], WhatsAppInstance::STATUS_FAILED);
            }
            throw $e;
        }
    }
    
    public function getInstanceStatus($instanceId) {
        try {
            $instance = $this->instanceModel->findById($instanceId);
            if (!$instance) {
                throw new Exception("Instance not found");
            }
            
            $cacheKey = "whatsapp:status:{$instanceId}";
            $cachedStatus = $this->redis->get($cacheKey);
            
            if ($cachedStatus) {
                return $cachedStatus;
            }
            
            $response = $this->evolutionAPI->getInstanceStatus($instance['instance_name']);
            
            $status = [
                'instance_id' => $instanceId,
                'instance_name' => $instance['instance_name'],
                'status' => $this->mapEvolutionStatus($response['instance']['state'] ?? 'disconnected'),
                'phone_number' => $instance['phone_number'],
                'last_check' => date('Y-m-d H:i:s'),
                'evolution_response' => $response
            ];
            
            if ($status['status'] !== $instance['status']) {
                $this->instanceModel->updateStatus($instanceId, $status['status']);
            }
            
            $this->redis->set($cacheKey, $status, CACHE_TTL_INSTANCES);
            
            return $status;
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to get instance status: " . $e->getMessage());
            return [
                'instance_id' => $instanceId,
                'status' => WhatsAppInstance::STATUS_FAILED,
                'error' => $e->getMessage(),
                'last_check' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    public function generateQRCode($instanceId) {
        try {
            $instance = $this->instanceModel->findById($instanceId);
            if (!$instance) {
                throw new Exception("Instance not found");
            }
            
            if ($instance['status'] === WhatsAppInstance::STATUS_CONNECTED) {
                return [
                    'success' => false,
                    'message' => 'Instance is already connected',
                    'status' => $instance['status']
                ];
            }
            
            $cacheKey = "whatsapp:qr:{$instanceId}";
            $cachedQR = $this->redis->get($cacheKey);
            
            if ($cachedQR && $this->instanceModel->hasValidQRCode($instanceId)) {
                return $cachedQR;
            }
            
            $response = $this->evolutionAPI->connectInstance($instance['instance_name']);
            
            if (isset($response['base64'])) {
                $qrData = [
                    'instance_id' => $instanceId,
                    'qr_code' => $response['base64'],
                    'generated_at' => date('Y-m-d H:i:s'),
                    'expires_at' => date('Y-m-d H:i:s', time() + CACHE_TTL_QR_CODE)
                ];
                
                $this->instanceModel->updateQRCode($instanceId, $response['base64']);
                $this->redis->set($cacheKey, $qrData, CACHE_TTL_QR_CODE);
                
                Logger::getInstance()->info("QR code generated", ['instance_id' => $instanceId]);
                
                return $qrData;
            } else {
                throw new Exception('No QR code received from Evolution API');
            }
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to generate QR code: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function restartInstance($instanceId) {
        try {
            $instance = $this->instanceModel->findById($instanceId);
            if (!$instance) {
                throw new Exception("Instance not found");
            }
            
            $this->instanceModel->updateStatus($instanceId, WhatsAppInstance::STATUS_CONNECTING);
            $response = $this->evolutionAPI->restartInstance($instance['instance_name']);
            
            $this->redis->delete("whatsapp:status:{$instanceId}");
            $this->redis->delete("whatsapp:qr:{$instanceId}");
            
            Logger::getInstance()->info("Instance restarted", ['instance_id' => $instanceId]);
            
            return $response;
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to restart instance: " . $e->getMessage());
            $this->instanceModel->updateStatus($instanceId, WhatsAppInstance::STATUS_FAILED);
            throw $e;
        }
    }
    
    public function disconnectInstance($instanceId) {
        try {
            $instance = $this->instanceModel->findById($instanceId);
            if (!$instance) {
                throw new Exception("Instance not found");
            }
            
            $response = $this->evolutionAPI->logoutInstance($instance['instance_name']);
            $this->instanceModel->updateStatus($instanceId, WhatsAppInstance::STATUS_DISCONNECTED);
            
            $this->redis->delete("whatsapp:status:{$instanceId}");
            $this->redis->delete("whatsapp:instance:{$instanceId}");
            
            Logger::getInstance()->info("Instance disconnected", ['instance_id' => $instanceId]);
            
            return $response;
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to disconnect instance: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function deleteInstance($instanceId) {
        try {
            $instance = $this->instanceModel->findById($instanceId);
            if (!$instance) {
                throw new Exception("Instance not found");
            }
            
            try {
                $this->evolutionAPI->deleteInstance($instance['instance_name']);
            } catch (Exception $e) {
                Logger::getInstance()->warning("Failed to delete instance from Evolution API: " . $e->getMessage());
            }
            
            $this->instanceModel->delete($instanceId);
            
            $this->redis->delete("whatsapp:status:{$instanceId}");
            $this->redis->delete("whatsapp:instance:{$instanceId}");
            $this->redis->delete("whatsapp:qr:{$instanceId}");
            
            Logger::getInstance()->info("Instance deleted", ['instance_id' => $instanceId]);
            
            return true;
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to delete instance: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function onInstanceConnected($instanceId, $connectionData) {
        try {
            $instance = $this->instanceModel->findById($instanceId);
            if (!$instance) {
                throw new Exception("Instance not found");
            }
            
            $updateData = [
                'status' => WhatsAppInstance::STATUS_CONNECTED,
                'phone_number' => $connectionData['phoneNumber'] ?? $instance['phone_number']
            ];
            
            if (isset($connectionData['profilePicture'])) {
                $updateData['profile_picture'] = $connectionData['profilePicture'];
            }
            
            $this->instanceModel->update($instanceId, $updateData);
            
            // Update user's owner_jid if available
            if (isset($connectionData['ownerJid']) && $instance['user_id']) {
                require_once __DIR__ . '/../Models/User.php';
                $userModel = new User();
                $userModel->updateOwnerJid($instance['user_id'], $connectionData['ownerJid']);
                
                Logger::getInstance()->info("Updated user owner_jid", [
                    'user_id' => $instance['user_id'],
                    'owner_jid' => $connectionData['ownerJid']
                ]);
            }
            
            $this->redis->delete("whatsapp:qr:{$instanceId}");
            
            $this->queueInitialSync($instanceId);
            
            Logger::getInstance()->info("Instance connected successfully", [
                'instance_id' => $instanceId,
                'phone_number' => $updateData['phone_number']
            ]);
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to handle instance connection: " . $e->getMessage());
        }
    }
    
    public function onInstanceDisconnected($instanceId) {
        try {
            $this->instanceModel->updateStatus($instanceId, WhatsAppInstance::STATUS_DISCONNECTED);
            
            $this->redis->delete("whatsapp:status:{$instanceId}");
            
            Logger::getInstance()->info("Instance disconnected", ['instance_id' => $instanceId]);
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to handle instance disconnection: " . $e->getMessage());
        }
    }
    
    public function syncInstanceData($instanceId) {
        $syncId = $this->syncLogModel->start($instanceId, WhatsAppSyncLog::TYPE_FULL);
        
        try {
            $recordsProcessed = 0;
            $recordsCreated = 0;
            $recordsUpdated = 0;
            
            $this->queue->pushHighPriority('sync_contacts', [
                'instance_id' => $instanceId,
                'sync_id' => $syncId
            ], $instanceId);
            
            $this->queue->pushHighPriority('sync_messages', [
                'instance_id' => $instanceId,
                'sync_id' => $syncId,
                'history_days' => WHATSAPP_SYNC_HISTORY_DAYS
            ], $instanceId);
            
            $this->queue->push('sync_groups', [
                'instance_id' => $instanceId,
                'sync_id' => $syncId
            ], MessageQueue::PRIORITY_NORMAL, $instanceId);
            
            Logger::getInstance()->info("Initial sync jobs queued", ['instance_id' => $instanceId, 'sync_id' => $syncId]);
            
        } catch (Exception $e) {
            $this->syncLogModel->fail($syncId, $e->getMessage());
            Logger::getInstance()->error("Failed to sync instance data: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getInstanceByUserId($userId) {
        return $this->instanceModel->findByUserId($userId);
    }
    
    public function getInstanceByName($instanceName) {
        return $this->instanceModel->findByInstanceName($instanceName);
    }
    
    public function isInstanceHealthy($instanceId) {
        try {
            $status = $this->getInstanceStatus($instanceId);
            return $status['status'] === WhatsAppInstance::STATUS_CONNECTED;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function healthCheckAll() {
        try {
            $connectedInstances = $this->instanceModel->getConnectedInstances();
            $results = [];
            
            foreach ($connectedInstances as $instance) {
                $results[$instance['id']] = $this->isInstanceHealthy($instance['id']);
                
                if (!$results[$instance['id']]) {
                    $this->onInstanceDisconnected($instance['id']);
                }
            }
            
            return $results;
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Health check failed: " . $e->getMessage());
            return [];
        }
    }
    
    public function getInstanceStats() {
        return $this->instanceModel->getInstanceStats();
    }
    
    private function queueInitialSync($instanceId) {
        try {
            $this->queue->pushHighPriority('initial_sync', [
                'instance_id' => $instanceId,
                'sync_contacts' => true,
                'sync_messages' => true,
                'sync_groups' => true,
                'history_days' => WHATSAPP_SYNC_HISTORY_DAYS
            ], $instanceId);
            
            Logger::getInstance()->info("Initial sync queued for connected instance", ['instance_id' => $instanceId]);
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to queue initial sync: " . $e->getMessage());
        }
    }
    
    private function mapEvolutionStatus($evolutionStatus) {
        switch (strtolower($evolutionStatus)) {
            case 'open':
            case 'connected':
                return WhatsAppInstance::STATUS_CONNECTED;
            case 'connecting':
            case 'pairing':
                return WhatsAppInstance::STATUS_CONNECTING;
            case 'close':
            case 'closed':
            case 'disconnected':
                return WhatsAppInstance::STATUS_DISCONNECTED;
            case 'creating':
                return WhatsAppInstance::STATUS_CREATING;
            default:
                return WhatsAppInstance::STATUS_FAILED;
        }
    }
    
    public function setInstanceSettings($instanceId, $settings) {
        try {
            $instance = $this->instanceModel->findById($instanceId);
            if (!$instance) {
                throw new Exception("Instance not found");
            }
            
            $response = $this->evolutionAPI->setSettings($instance['instance_name'], $settings);
            $this->instanceModel->updateSettings($instanceId, $settings);
            
            Logger::getInstance()->info("Instance settings updated", ['instance_id' => $instanceId]);
            
            return $response;
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to set instance settings: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function setInstanceWebhook($instanceId, $webhookUrl = null, $events = null) {
        try {
            $instance = $this->instanceModel->findById($instanceId);
            if (!$instance) {
                throw new Exception("Instance not found");
            }
            
            $webhookUrl = $webhookUrl ?: WHATSAPP_WEBHOOK_URL;
            $events = $events ?: explode(',', WEBHOOK_ENABLED_EVENTS);
            
            $response = $this->evolutionAPI->setWebhook($instance['instance_name'], $webhookUrl, $events);
            $this->instanceModel->updateWebhookUrl($instanceId, $webhookUrl);
            
            Logger::getInstance()->info("Instance webhook updated", ['instance_id' => $instanceId, 'webhook_url' => $webhookUrl]);
            
            return $response;
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to set instance webhook: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getInstancesSyncQueue() {
        return $this->instanceModel->getInstancesNeedingSync(24);
    }
    
    public function cleanupDisconnectedInstances($hoursDisconnected = 168) { // 7 days
        try {
            $sql = "UPDATE whatsapp_instances 
                    SET status = ? 
                    WHERE status = ? 
                    AND updated_at < DATE_SUB(NOW(), INTERVAL ? HOUR)";
            
            $count = $this->instanceModel->getConnection()->query($sql, [
                WhatsAppInstance::STATUS_FAILED,
                WhatsAppInstance::STATUS_DISCONNECTED,
                $hoursDisconnected
            ])->rowCount();
            
            Logger::getInstance()->info("Cleaned up disconnected instances", ['count' => $count]);
            
            return $count;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to cleanup disconnected instances: " . $e->getMessage());
            return 0;
        }
    }
}