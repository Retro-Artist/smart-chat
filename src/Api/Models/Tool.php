<?php
// src/Api/Models/Tool.php - ABSTRACT BASE CLASS

abstract class Tool {
    abstract public function getName(): string;
    abstract public function getDescription(): string;
    abstract public function getParametersSchema(): array;
    abstract public function execute(array $parameters): array;
    
    public function getOpenAIDefinition(): array {
        $schema = $this->getParametersSchema();
        $required = [];
        
        // Extract required parameters correctly
        foreach ($schema as $paramName => $paramConfig) {
            if (isset($paramConfig['required']) && $paramConfig['required'] === true) {
                $required[] = $paramName;
            }
        }
        
        // Clean the schema - remove the 'required' field from individual parameters
        $cleanSchema = [];
        foreach ($schema as $paramName => $paramConfig) {
            $cleanSchema[$paramName] = [
                'type' => $paramConfig['type'],
                'description' => $paramConfig['description']
            ];
        }
        
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => $this->getDescription(),
                'parameters' => [
                    'type' => 'object',
                    'properties' => $cleanSchema,
                    'required' => $required
                ]
            ]
        ];
    }
    
    public function safeExecute(array $parameters): array {
        try {
            $this->validateParameters($parameters);
            return $this->execute($parameters);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tool' => $this->getName()
            ];
        }
    }
    
    protected function validateParameters(array $parameters): bool {
        $schema = $this->getParametersSchema();
        
        // Check required parameters
        foreach ($schema as $param => $config) {
            if (isset($config['required']) && $config['required'] && !isset($parameters[$param])) {
                throw new InvalidArgumentException("Missing required parameter: {$param}");
            }
        }
        
        return true;
    }
}