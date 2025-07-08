<?php
// src/Web/Controllers/DashboardController.php  

require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../../Api/Models/Thread.php';
require_once __DIR__ . '/../../Api/Models/Agent.php';
require_once __DIR__ . '/../../Api/Models/Run.php';

class DashboardController
{

    public function index()
    {
        // Check if user is logged in
        Helpers::requireWebAuth();

        $userId = Helpers::getCurrentUserId();

        // Get user's agents
        $agents = Agent::getUserAgents($userId);

        // Get recent threads
        $recentThreads = Thread::getRecentThreads($userId, 5);

        // Get run statistics
        $runStats = Run::getRunStats($userId);

        // Get recent runs
        $recentRuns = Run::getUserRuns($userId, 10);

        // Calculate agent statistics
        $agentStats = [
            'total' => count($agents),
            'active' => count(array_filter($agents, fn($a) => $a->isActive())),
            'with_tools' => count(array_filter($agents, fn($a) => !empty($a->getTools())))
        ];

        // Calculate thread statistics
        $allThreads = Thread::getUserThreads($userId);
        $threadStats = [
            'total' => count($allThreads),
            'with_messages' => count(array_filter($allThreads, fn($t) => $t['message_count'] > 0)),
            'recent' => count(array_filter(
                $allThreads,
                fn($t) =>
                strtotime($t['created_at']) > strtotime('-7 days')
            ))
        ];

        // Get real conversation data for charts
        $conversationChartData = $this->getConversationChartData($userId);
        $agentPerformanceData = $this->getAgentPerformanceData($userId);

        // Load dashboard view with real data
        Helpers::loadView('dashboard', [
            'pageTitle' => 'Dashboard - OpenAI Webchat',
            'agents' => $agents,
            'recentThreads' => $recentThreads,
            'recentRuns' => $recentRuns,
            'agentStats' => $agentStats,
            'threadStats' => $threadStats,
            'runStats' => $runStats ?: [
                'total_runs' => 0,
                'completed_runs' => 0,
                'failed_runs' => 0,
                'running_runs' => 0
            ],
            // Add real chart data
            'conversationChartData' => $conversationChartData,
            'agentPerformanceData' => $agentPerformanceData
        ]);
    }

    /**
     * Get real conversation data for the last 7 days
     */
    private function getConversationChartData($userId)
    {
        $db = Database::getInstance();

        // Get conversation counts for last 7 days
        $sql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM threads 
            WHERE user_id = ? 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";

        $results = $db->fetchAll($sql, [$userId]);

        // Create complete 7-day dataset with zero values for missing days
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $label = date('M j', strtotime("-{$i} days"));
            $labels[] = $label;

            // Find count for this date
            $count = 0;
            foreach ($results as $result) {
                if ($result['date'] === $date) {
                    $count = (int)$result['count'];
                    break;
                }
            }
            $data[] = $count;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    /**
     * Get real agent performance data
     */
    private function getAgentPerformanceData($userId)
    {
        $db = Database::getInstance();

        // Get run statistics
        $sql = "
            SELECT 
                status,
                COUNT(*) as count
            FROM runs r
            JOIN agents a ON r.agent_id = a.id
            WHERE a.user_id = ?
            GROUP BY status
        ";

        $results = $db->fetchAll($sql, [$userId]);

        $completed = 0;
        $failed = 0;
        $running = 0;

        foreach ($results as $result) {
            switch ($result['status']) {
                case 'completed':
                    $completed = (int)$result['count'];
                    break;
                case 'failed':
                    $failed = (int)$result['count'];
                    break;
                case 'running':
                case 'pending':
                    $running += (int)$result['count'];
                    break;
            }
        }

        return [
            'completed' => $completed,
            'failed' => $failed,
            'pending' => $running
        ];
    }
}
