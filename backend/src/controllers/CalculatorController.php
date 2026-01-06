<?php

namespace Construkt\Controllers;

use Construkt\Services\CalculatorService;

/**
 * CalculatorController - Handles material calculation API endpoints
 */
class CalculatorController {
    private $calculatorService;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->calculatorService = new CalculatorService();
    }
    
    /**
     * Calculate area-based material quantities
     * 
     * @param array $data Request data
     * @return array Response with calculation results
     */
    public function calculateArea($data) {
        // Validate required parameters
        if (!isset($data['length']) || !isset($data['width']) || !isset($data['coverage'])) {
            return [
                'success' => false,
                'error' => 'Missing required parameters: length, width, coverage'
            ];
        }
        
        $length = floatval($data['length']);
        $width = floatval($data['width']);
        $coverage = floatval($data['coverage']);
        $wastage = isset($data['wastage']) ? floatval($data['wastage']) / 100 : 0.1; // Convert percentage to decimal
        
        return $this->calculatorService->calculateAreaMaterial($length, $width, $coverage, $wastage);
    }
    
    /**
     * Calculate volume-based material quantities
     * 
     * @param array $data Request data
     * @return array Response with calculation results
     */
    public function calculateVolume($data) {
        // Validate required parameters
        if (!isset($data['length']) || !isset($data['width']) || !isset($data['depth'])) {
            return [
                'success' => false,
                'error' => 'Missing required parameters: length, width, depth'
            ];
        }
        
        $length = floatval($data['length']);
        $width = floatval($data['width']);
        $depth = floatval($data['depth']);
        $wastage = isset($data['wastage']) ? floatval($data['wastage']) / 100 : 0.15; // Convert percentage to decimal
        
        return $this->calculatorService->calculateVolumeMaterial($length, $width, $depth, $wastage);
    }
    
    /**
     * Calculate linear material quantities
     * 
     * @param array $data Request data
     * @return array Response with calculation results
     */
    public function calculateLinear($data) {
        // Validate required parameters
        if (!isset($data['length'])) {
            return [
                'success' => false,
                'error' => 'Missing required parameter: length'
            ];
        }
        
        $length = floatval($data['length']);
        $pieceLength = isset($data['pieceLength']) ? floatval($data['pieceLength']) : null;
        $wastage = isset($data['wastage']) ? floatval($data['wastage']) / 100 : 0.05; // Convert percentage to decimal
        
        return $this->calculatorService->calculateLinearMaterial($length, $pieceLength, $wastage);
    }
    
    /**
     * Generic calculate endpoint that routes to the appropriate calculation method
     * 
     * @param array $data Request data
     * @return array Response with calculation results
     */
    public function calculate($data) {
        if (!isset($data['type'])) {
            return [
                'success' => false,
                'error' => 'Missing required parameter: type (area, volume, or linear)'
            ];
        }
        
        switch ($data['type']) {
            case 'area':
                return $this->calculateArea($data);
            case 'volume':
                return $this->calculateVolume($data);
            case 'linear':
                return $this->calculateLinear($data);
            default:
                return [
                    'success' => false,
                    'error' => 'Invalid calculation type. Supported types: area, volume, linear'
                ];
        }
    }
}
