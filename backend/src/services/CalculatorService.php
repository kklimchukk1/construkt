<?php

namespace Construkt\Services;

/**
 * CalculatorService - Handles material quantity calculations
 * 
 * This service provides methods for calculating material quantities
 * based on different measurement types (area, volume, linear).
 */
class CalculatorService {
    
    /**
     * Calculate material quantity for area-based materials
     * 
     * @param float $length Length in meters
     * @param float $width Width in meters
     * @param float $coverage Coverage per unit (m²) of material
     * @param float $wastage Wastage factor (default 10%)
     * @return array Calculation result with quantity and details
     */
    public function calculateAreaMaterial($length, $width, $coverage, $wastage = 0.1) {
        // Validate inputs
        if (!is_numeric($length) || !is_numeric($width) || !is_numeric($coverage) || $coverage <= 0) {
            return [
                'success' => false,
                'error' => 'Invalid input parameters'
            ];
        }
        
        // Calculate area
        $area = $length * $width;
        
        // Calculate required quantity with wastage
        $requiredQuantity = ($area / $coverage) * (1 + $wastage);
        
        return [
            'success' => true,
            'result' => [
                'area' => round($area, 2),
                'requiredQuantity' => ceil($requiredQuantity),
                'wastagePercentage' => $wastage * 100,
                'wastageAmount' => round($requiredQuantity - ($area / $coverage), 2)
            ]
        ];
    }
    
    /**
     * Calculate material quantity for volume-based materials
     * 
     * @param float $length Length in meters
     * @param float $width Width in meters
     * @param float $depth Depth/height in meters
     * @param float $wastage Wastage factor (default 15%)
     * @return array Calculation result with quantity and details
     */
    public function calculateVolumeMaterial($length, $width, $depth, $wastage = 0.15) {
        // Validate inputs
        if (!is_numeric($length) || !is_numeric($width) || !is_numeric($depth)) {
            return [
                'success' => false,
                'error' => 'Invalid input parameters'
            ];
        }
        
        // Calculate volume in cubic meters
        $volume = $length * $width * $depth;
        
        // Calculate required quantity with wastage
        $requiredVolume = $volume * (1 + $wastage);
        
        return [
            'success' => true,
            'result' => [
                'volume' => round($volume, 2),
                'requiredVolume' => round($requiredVolume, 2),
                'wastagePercentage' => $wastage * 100,
                'wastageAmount' => round($requiredVolume - $volume, 2)
            ]
        ];
    }
    
    /**
     * Calculate material quantity for linear materials
     * 
     * @param float $length Length in meters
     * @param float $pieceLength Length of each piece in meters
     * @param float $wastage Wastage factor (default 5%)
     * @return array Calculation result with quantity and details
     */
    public function calculateLinearMaterial($length, $pieceLength = null, $wastage = 0.05) {
        // Validate inputs
        if (!is_numeric($length)) {
            return [
                'success' => false,
                'error' => 'Invalid input parameters'
            ];
        }
        
        // Calculate required length with wastage
        $requiredLength = $length * (1 + $wastage);
        
        $result = [
            'success' => true,
            'result' => [
                'length' => round($length, 2),
                'requiredLength' => round($requiredLength, 2),
                'wastagePercentage' => $wastage * 100,
                'wastageAmount' => round($requiredLength - $length, 2)
            ]
        ];
        
        // If piece length is provided, calculate number of pieces needed
        if ($pieceLength && is_numeric($pieceLength) && $pieceLength > 0) {
            $piecesNeeded = ceil($requiredLength / $pieceLength);
            $result['result']['pieceLength'] = $pieceLength;
            $result['result']['piecesNeeded'] = $piecesNeeded;
        }
        
        return $result;
    }
}
