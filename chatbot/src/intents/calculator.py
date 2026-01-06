"""
Calculator intent handler for the construction materials chatbot
"""
import re
import json
import requests

class CalculatorIntentHandler:
    """
    Handles calculator intents and extracts dimensions from user messages
    """
    
    def __init__(self, api_base_url=None):
        """
        Initialize the calculator intent handler
        
        Args:
            api_base_url (str, optional): Base URL for the calculator API
        """
        # Set API base URL (default to localhost if not provided)
        self.api_base_url = api_base_url or 'http://localhost:5000/api'
        
        # Define required dimensions for each material type
        self.required_dimensions = {
            'area': ['length', 'width'],
            'volume': ['length', 'width', 'depth'],
            'linear': ['length']
        }
        
        # Define patterns for detecting material types
        self.material_type_patterns = {
            'area': [
                r'(?:area|surface|wall|floor|ceiling|paint|tile)',
                r'(?:square|sq\.?)\s*(?:meter|m|metre)',
                r'm2|m\u00b2'
            ],
            'volume': [
                r'(?:volume|concrete|cement|sand|gravel|fill)',
                r'(?:cubic|cu\.?)\s*(?:meter|m|metre)',
                r'm3|m\u00b3'
            ],
            'linear': [
                r'(?:linear|length|pipe|cable|wire|trim|molding)',
                r'(?:meter|m|metre)\s*(?:long|length)',
                r'(?:running|ln)\s*(?:meter|m|metre)'
            ]
        }
        
        # Define dimension extraction patterns
        self.dimension_patterns = {
            'length': [
                r'(\d+(?:\.\d+)?)\s*(?:m|meter|meters|metre|metres)?\s*(?:long|length|by|x)',
                r'length\s*(?:of|is|:)?\s*(\d+(?:\.\d+)?)',
                r'(\d+(?:\.\d+)?)\s*(?:m|meter|meters|metre|metres)?\s*(?:in length)',
                r'(\d+(?:\.\d+)?)\s*(?:m|meter|meters|metre|metres)?\s*(?:by|x)',
                r'(\d+)\s*(?:m|meter|meters|metre|metres)\s*(?:long|length)',
                r'(\d+)\s*(?:by|x)'
            ],
            'width': [
                r'(\d+(?:\.\d+)?)\s*(?:m|meter|meters|metre|metres)?\s*(?:wide|width)',
                r'width\s*(?:of|is|:)?\s*(\d+(?:\.\d+)?)',
                r'(\d+(?:\.\d+)?)\s*(?:m|meter|meters|metre|metres)?\s*(?:in width)',
                r'(?:by|x)\s*(\d+(?:\.\d+)?)\s*(?:m|meter|meters|metre|metres)?'
            ],
            'height': [
                r'(\d+(?:\.\d+)?)\s*(?:m|meter|meters|metre|metres)?\s*(?:high|height|tall)',
                r'height\s*(?:of|is|:)?\s*(\d+(?:\.\d+)?)',
                r'(\d+(?:\.\d+)?)\s*(?:m|meter|meters|metre|metres)?\s*(?:in height)',
                r'(?:by|x)\s*(\d+(?:\.\d+)?)\s*(?:m|meter|meters|metre|metres)?\s*(?:high|height|tall)'
            ],
            'depth': [
                r'(\d+(?:\.\d+)?)\s*(?:m|meter|meters|metre|metres)?\s*(?:deep|depth)',
                r'depth\s*(?:of|is|:)?\s*(\d+(?:\.\d+)?)',
                r'(\d+(?:\.\d+)?)\s*(?:m|meter|meters|metre|metres)?\s*(?:in depth)',
                r'(?:by|x)\s*(\d+(?:\.\d+)?)\s*(?:m|meter|meters|metre|metres)?\s*(?:deep|depth)'
            ],
            'coverage': [
                r'(?:coverage|covers)\s*(?:of|is|:)?\s*(\d+(?:\.\d+)?)',
                r'(\d+(?:\.\d+)?)\s*(?:m2|m²|sq\.?\s*m|square\s*meters?|square\s*metres?)\s*(?:per|/|coverage)'
            ],
            'wastage': [
                r'(?:wastage|waste)\s*(?:of|is|:)?\s*(\d+(?:\.\d+)?)\s*(?:%|percent)',
                r'(\d+(?:\.\d+)?)\s*(?:%|percent)\s*(?:wastage|waste)'
            ]
        }
        
    def extract_dimensions(self, message):
        """
        Extract dimensions from a user message
        
        Args:
            message (str): User message
            
        Returns:
            dict: Extracted dimensions
        """
        dimensions = {}
        
        # Extract each dimension using regex patterns
        for dim_name, patterns in self.dimension_patterns.items():
            for pattern in patterns:
                match = re.search(pattern, message.lower())
                if match and match.group(1):
                    try:
                        # Convert to float and store
                        dimensions[dim_name] = float(match.group(1))
                        break  # Stop after first match for this dimension
                    except (ValueError, IndexError):
                        pass
        
        return dimensions
    
    def detect_material_type(self, message):
        """
        Detect material type from message
        
        Args:
            message (str): User message
            
        Returns:
            str: Material type or None if not detected
        """
        message_lower = message.lower()
        
        # Check each material type using regex patterns
        for material_type, patterns in self.material_type_patterns.items():
            for pattern in patterns:
                if re.search(pattern, message_lower):
                    return material_type
        
        return None
        
    def _calculate_locally(self, material_type, dimensions):
        """
        Perform local calculation instead of API request
        
        Args:
            material_type (str): Type of material (area, volume, linear)
            dimensions (dict): Dimensions for calculation
            
        Returns:
            dict: Calculation results
        """
        try:
            # Get dimensions
            result = {}
            
            # Calculate area
            if material_type == 'area':
                length = float(dimensions.get('length', 0))
                width = float(dimensions.get('width', 0))
                area = length * width
                result['area'] = area
                
                # Calculate required quantity based on coverage
                coverage = float(dimensions.get('coverage', 10))  # Default: 10 m² per unit
                wastage_percentage = float(dimensions.get('wastage', 10))  # Default: 10% wastage
                
                # Check if we have product dimensions from the database
                product_id = dimensions.get('product_id')
                if product_id:
                    try:
                        # Import here to avoid circular imports
                        from utils.database import DatabaseConnector
                        db = DatabaseConnector()
                        
                        # Get product from database
                        product = db.get_products(product_id=product_id)
                        if product and len(product) > 0:
                            product = product[0]  # Get the first product
                            
                            # Check if product has dimensions
                            if product.get('dimensions'):
                                try:
                                    # Parse dimensions JSON
                                    product_dimensions = json.loads(product['dimensions'])
                                    print(f"Found product dimensions: {product_dimensions}")
                                    
                                    # Use product-specific coverage if available
                                    if product_dimensions.get('coverage'):
                                        coverage = float(product_dimensions['coverage'])
                                        print(f"Using product-specific coverage: {coverage}")
                                except Exception as e:
                                    print(f"Error parsing product dimensions: {e}")
                    except Exception as e:
                        print(f"Error getting product dimensions: {e}")
                
                # Fallback to default coverage values based on product type
                if product_id and str(product_id) == '8' and coverage == 10:
                    # For ordinary hollow brick, approximately 50 bricks per m²
                    coverage = 0.02  # 50 bricks per m²
                
                # Calculate required quantity
                required_quantity = area / coverage
                wastage_amount = (wastage_percentage / 100) * required_quantity
                total_required = required_quantity + wastage_amount
                
                # Round up to nearest whole number for most materials
                result['requiredQuantity'] = round(total_required)
                result['wastagePercentage'] = wastage_percentage
                result['wastageAmount'] = wastage_amount
                
            # Calculate volume
            elif material_type == 'volume':
                length = float(dimensions.get('length', 0))
                width = float(dimensions.get('width', 0))
                depth = float(dimensions.get('depth', 0))
                volume = length * width * depth
                result['volume'] = volume
                
                # Calculate required volume based on coverage
                wastage_percentage = float(dimensions.get('wastage', 15))  # Default: 15% wastage
                wastage_amount = (wastage_percentage / 100) * volume
                total_required = volume + wastage_amount
                
                result['requiredVolume'] = round(total_required, 2)
                result['wastagePercentage'] = wastage_percentage
                result['wastageAmount'] = round(wastage_amount, 2)
                
            # Calculate linear
            elif material_type == 'linear':
                length = float(dimensions.get('length', 0))
                wastage_percentage = float(dimensions.get('wastage', 5))  # Default: 5% wastage
                
                # Calculate required length
                wastage_amount = (wastage_percentage / 100) * length
                total_required = length + wastage_amount
                
                result['requiredLength'] = round(total_required, 2)
                result['wastagePercentage'] = wastage_percentage
                result['wastageAmount'] = round(wastage_amount, 2)
                
                # Calculate pieces needed if piece length is provided
                if 'piece_length' in dimensions:
                    piece_length = float(dimensions.get('piece_length', 0))
                    if piece_length > 0:
                        pieces_needed = total_required / piece_length
                        result['piecesNeeded'] = round(pieces_needed)
                        result['pieceLength'] = piece_length
            
            return result
            
        except Exception as e:
            print(f"Error in local calculation: {e}")
            return {
                'error': f"Calculation failed: {str(e)}"
            }

    def has_dimensions(self, message, dimension_list):
        """
        Check if the message contains specific dimensions
        
        Args:
            message (str): User message
            dimension_list (list): List of dimensions to check for
            
        Returns:
            bool: True if all dimensions are present, False otherwise
        """
        message_lower = message.lower()
        
        for dimension in dimension_list:
            found = False
            for pattern in self.dimension_patterns.get(dimension, []):
                if re.search(pattern, message_lower):
                    found = True
                    break
            if not found:
                return False
        
        return True
    
    def calculate(self, material_type, dimensions):
        """
        Calculate material quantities using the API
        
        Args:
            material_type (str): Type of material (area, volume, linear)
            dimensions (dict): Dimensions for calculation
            
        Returns:
            dict: Calculation results
        """
        try:
            # Handle height dimension for area calculations (convert height to width if needed)
            if material_type == 'area' and 'height' in dimensions and 'width' not in dimensions:
                dimensions['width'] = dimensions['height']
            
            # Set default values for missing dimensions
            if material_type == 'area' and 'coverage' not in dimensions:
                # For bricks, we need a more realistic coverage value
                # Standard brick wall requires about 50-60 bricks per square meter
                product_id = dimensions.get('product_id')
                if product_id and str(product_id) == '8':  # Ordinary hollow brick ID
                    dimensions['coverage'] = 0.02  # 50 bricks per m²
                else:
                    dimensions['coverage'] = 10  # Default coverage: 10 m² per unit
            
            if 'wastage' not in dimensions:
                # Default wastage percentages by material type
                default_wastage = {
                    'area': 10,
                    'volume': 15,
                    'linear': 5
                }
                dimensions['wastage'] = default_wastage.get(material_type, 10)
            
            # Perform local calculation instead of API request
            result = self._calculate_locally(material_type, dimensions)
            return {
                'success': True,
                'result': result
            }
        
        except Exception as e:
            print(f"Error calculating materials: {e}")
            return {
                'success': False,
                'error': f"Calculation failed: {str(e)}"
            }
    
    def format_calculation_response(self, material_type, calculation_result, dimensions):
        """
        Format the calculation response based on the material type and result
        
        Args:
            material_type (str): Type of material calculation
            calculation_result (dict): Result of the calculation
            dimensions (dict): Dimensions used for the calculation
            
        Returns:
            str: Formatted response message
        """
        # Check if this is a recalculation (follow-up question)
        is_recalculation = dimensions.get('_is_recalculation', False)
        
        if not calculation_result.get('success', False):
            return "I'm sorry, I couldn't calculate the materials needed. Please check your dimensions and try again."
        
        result = calculation_result.get('result', {})
        
        if material_type == 'area':
            if is_recalculation:
                response = f"With the updated dimensions (length: {dimensions.get('length')}m, width: {dimensions.get('width')}m"
                if 'height' in dimensions:
                    response += f", height: {dimensions.get('height')}m"
                response += "), "
            else:
                response = f"Based on your dimensions (length: {dimensions.get('length')}m, width: {dimensions.get('width')}m"
                if 'height' in dimensions:
                    response += f", height: {dimensions.get('height')}m"
                response += "), "
            
            response += f"the total area is {result.get('area', 0)} m². "
            response += f"You will need approximately {result.get('requiredQuantity', 0)} units of material"
            
            if 'wastage' in dimensions:
                response += f" (including {dimensions['wastage']}% wastage)."
            else:
                response += f" (including {result.get('wastagePercentage', 0)}% wastage)."
            
            return response
        
        elif material_type == 'volume':
            if is_recalculation:
                response = f"With the updated dimensions (length: {dimensions.get('length')}m, width: {dimensions.get('width')}m, depth: {dimensions.get('depth')}m), "
            else:
                response = f"Based on your dimensions (length: {dimensions.get('length')}m, width: {dimensions.get('width')}m, depth: {dimensions.get('depth')}m), "
            
            response += f"the total volume is {result.get('volume', 0)} m³. "
            response += f"You will need approximately {result.get('requiredVolume', 0)} m³ of material"
            
            if 'wastage' in dimensions:
                response += f" (including {dimensions['wastage']}% wastage)."
            else:
                response += f" (including {result.get('wastagePercentage', 0)}% wastage)."
            
            return response
        
        elif material_type == 'linear':
            if is_recalculation:
                response = f"With the updated dimensions (length: {dimensions.get('length')}m), "
            else:
                response = f"Based on your dimensions (length: {dimensions.get('length')}m), "
            
            response += f"you will need approximately {result.get('requiredLength', 0)} m of material"
            
            if 'wastage' in dimensions:
                response += f" (including {dimensions['wastage']}% wastage)."
            else:
                response += f" (including {result.get('wastagePercentage', 0)}% wastage)."
            
            if 'piecesNeeded' in result and 'pieceLength' in result:
                response += f" This equals about {result.get('piecesNeeded', 0)} pieces " \
                            f"at {result.get('pieceLength', 0)} m per piece."
            
            return response
        
        return "Calculation complete. Please check the results and let me know if you need any clarification."
    
    def handle_calculator_intent(self, message, context=None):
        """
        Handle a calculator intent
        
        Args:
            message (str): User message
            context (dict, optional): Conversation context
            
        Returns:
            dict: Response data
        """
        # Initialize context if not provided
        if context is None:
            context = {}
        
        # Get stored dimensions from context if available
        stored_dimensions = context.get('calculator_dimensions', {})
        stored_material_type = context.get('calculator_material_type', None)
        
        # Extract dimensions from current message
        current_dimensions = self.extract_dimensions(message)
        
        # Merge with stored dimensions, prioritizing current message
        dimensions = {**stored_dimensions, **current_dimensions}
        
        # Detect material type from current message or use stored type
        current_material_type = self.detect_material_type(message)
        material_type = current_material_type or stored_material_type
        
        # Check if message is just 'calculate' or similar after a product inquiry
        if re.match(r'^\s*(?:calculate|calculator|calc|estimate|how much)\s*$', message.strip().lower()):
            # This is a generic calculator request, check if we have product context
            product_id = context.get('current_product_id')
            if product_id:
                print(f"Handling calculator request with product ID: {product_id}")
                
                # We have a product ID, let's get the product dimensions
                try:
                    # Import here to avoid circular imports
                    from utils.database import DatabaseConnector
                    db = DatabaseConnector()
                    
                    # Get product from database
                    product = db.get_products(product_id=product_id)
                    if product and len(product) > 0:
                        product = product[0]  # Get the first product
                        print(f"Found product: {product['name']}")
                        
                        # Initialize dimensions with product ID
                        dimensions['product_id'] = product_id
                        
                        # Check if product has dimensions
                        if product.get('dimensions'):
                            try:
                                # Parse dimensions JSON
                                product_dimensions = json.loads(product['dimensions'])
                                print(f"Found product dimensions: {product_dimensions}")
                                
                                # Set material type from product dimensions
                                if product_dimensions.get('material_type'):
                                    material_type = product_dimensions['material_type']
                                else:
                                    material_type = 'area'  # Default to area
                                
                                # Return a prompt for dimensions
                                return {
                                    'message': f"I can help you calculate how much {product['name']} you'll need for your project. Please provide the dimensions, for example: 'I need to build a wall 4m long and 2.5m high'.",
                                    'data': {
                                        'context_update': {
                                            'calculator_dimensions': dimensions,
                                            'calculator_material_type': material_type,
                                            'calculator_state': 'awaiting_dimensions',
                                            'current_product_id': product_id
                                        }
                                    }
                                }
                            except Exception as e:
                                print(f"Error parsing product dimensions: {e}")
                except Exception as e:
                    print(f"Error getting product: {e}")
            
            # If we have a previous calculation, return it
            if 'calculator_state' in context and context['calculator_state'] == 'complete':
                calculator_result = context.get('calculator_last_result', {})
                calculator_dimensions = context.get('calculator_dimensions', {})
                calculator_material_type = context.get('calculator_material_type', 'area')
                
                response_message = self.format_calculation_response(
                    calculator_material_type, calculator_result, calculator_dimensions
                )
                
                return {
                    'message': response_message,
                    'data': {
                        'dimensions': calculator_dimensions,
                        'material_type': calculator_material_type,
                        'calculation_result': calculator_result
                    }
                }
        
        # Check for generic recalculation requests
        recalculation_match = re.search(r'(?:recalculate|calculate again|redo calculation)', message.strip().lower())
        if recalculation_match and material_type and all(dim in dimensions for dim in self.required_dimensions.get(material_type, [])):
            # Mark this as a recalculation
            dimensions['_is_recalculation'] = True
            
            # Calculate material quantities
            calculation_result = self.calculate(material_type, dimensions)
            
            # Format response
            response_message = self.format_calculation_response(material_type, calculation_result, dimensions)
            
            return {
                'message': response_message,
                'data': {
                    'dimensions': dimensions,
                    'material_type': material_type,
                    'calculation_result': calculation_result,
                    'context_update': {
                        'calculator_dimensions': dimensions,
                        'calculator_material_type': material_type,
                        'calculator_state': 'complete',
                        'calculator_last_result': calculation_result
                    }
                }
            }
        
        # Handle various types of follow-up questions
        
        # Check for follow-up questions about wastage
        wastage_match = re.search(r'(?:if|with|and|change|what about|how about)\s+(?:the\s+)?wastage\s+(?:is|to|of|at)?\s+(\d+(?:\.\d+)?)\s*%?', message.strip().lower())
        if wastage_match:
            # This is a follow-up question about changing the wastage percentage
            new_wastage = float(wastage_match.group(1))
            dimensions['wastage'] = new_wastage
            
            # If we have a previous calculation and all required dimensions, recalculate
            if material_type and all(dim in dimensions for dim in self.required_dimensions.get(material_type, [])):
                # Calculate material quantities with new wastage
                calculation_result = self.calculate(material_type, dimensions)
                
                # Format response
                response_message = self.format_calculation_response(material_type, calculation_result, dimensions)
                
                return {
                    'message': response_message,
                    'data': {
                        'dimensions': dimensions,
                        'material_type': material_type,
                        'calculation_result': calculation_result,
                        'context_update': {
                            'calculator_dimensions': dimensions,
                            'calculator_material_type': material_type,
                            'calculator_state': 'complete',
                            'calculator_last_result': calculation_result
                        }
                    }
                }
        
        # Check for follow-up questions about length
        length_match = re.search(r'(?:if|with|and|change|what about|how about)\s+(?:the\s+)?length\s+(?:is|to|of|at)?\s+(\d+(?:\.\d+)?)\s*(?:m|meters|metre|metres)?', message.strip().lower())
        if length_match:
            # This is a follow-up question about changing the length
            new_length = float(length_match.group(1))
            dimensions['length'] = new_length
            
            # If we have a previous calculation and all required dimensions, recalculate
            if material_type and all(dim in dimensions for dim in self.required_dimensions.get(material_type, [])):
                # Calculate material quantities with new length
                calculation_result = self.calculate(material_type, dimensions)
                
                # Format response
                response_message = self.format_calculation_response(material_type, calculation_result, dimensions)
                
                return {
                    'message': response_message,
                    'data': {
                        'dimensions': dimensions,
                        'material_type': material_type,
                        'calculation_result': calculation_result,
                        'context_update': {
                            'calculator_dimensions': dimensions,
                            'calculator_material_type': material_type,
                            'calculator_state': 'complete',
                            'calculator_last_result': calculation_result
                        }
                    }
                }
        
        # Check for follow-up questions about width
        width_match = re.search(r'(?:if|with|and|change|what about|how about)\s+(?:the\s+)?width\s+(?:is|to|of|at)?\s+(\d+(?:\.\d+)?)\s*(?:m|meters|metre|metres)?', message.strip().lower())
        if width_match:
            # This is a follow-up question about changing the width
            new_width = float(width_match.group(1))
            dimensions['width'] = new_width
            
            # If we have a previous calculation and all required dimensions, recalculate
            if material_type and all(dim in dimensions for dim in self.required_dimensions.get(material_type, [])):
                # Calculate material quantities with new width
                calculation_result = self.calculate(material_type, dimensions)
                
                # Format response
                response_message = self.format_calculation_response(material_type, calculation_result, dimensions)
                
                return {
                    'message': response_message,
                    'data': {
                        'dimensions': dimensions,
                        'material_type': material_type,
                        'calculation_result': calculation_result,
                        'context_update': {
                            'calculator_dimensions': dimensions,
                            'calculator_material_type': material_type,
                            'calculator_state': 'complete',
                            'calculator_last_result': calculation_result
                        }
                    }
                }
        
        # Check for follow-up questions about height
        height_match = re.search(r'(?:if|with|and|change|what about|how about)\s+(?:the\s+)?height\s+(?:is|to|of|at)?\s+(\d+(?:\.\d+)?)\s*(?:m|meters|metre|metres)?', message.strip().lower())
        if height_match:
            # This is a follow-up question about changing the height
            new_height = float(height_match.group(1))
            dimensions['height'] = new_height
            if material_type == 'area' and 'width' not in dimensions:
                dimensions['width'] = new_height  # Use height as width for area calculations
            
            # If we have a previous calculation and all required dimensions, recalculate
            if material_type and all(dim in dimensions for dim in self.required_dimensions.get(material_type, [])):
                # Calculate material quantities with new height
                calculation_result = self.calculate(material_type, dimensions)
                
                # Format response
                response_message = self.format_calculation_response(material_type, calculation_result, dimensions)
                
                return {
                    'message': response_message,
                    'data': {
                        'dimensions': dimensions,
                        'material_type': material_type,
                        'calculation_result': calculation_result,
                        'context_update': {
                            'calculator_dimensions': dimensions,
                            'calculator_material_type': material_type,
                            'calculator_state': 'complete',
                            'calculator_last_result': calculation_result
                        }
                    }
                }
        
        # Check for follow-up questions about depth
        depth_match = re.search(r'(?:if|with|and|change|what about|how about)\s+(?:the\s+)?depth\s+(?:is|to|of|at)?\s+(\d+(?:\.\d+)?)\s*(?:m|meters|metre|metres)?', message.strip().lower())
        if depth_match:
            # This is a follow-up question about changing the depth
            new_depth = float(depth_match.group(1))
            dimensions['depth'] = new_depth
            
            # If we have a previous calculation and all required dimensions, recalculate
            if material_type and all(dim in dimensions for dim in self.required_dimensions.get(material_type, [])):
                # Calculate material quantities with new depth
                calculation_result = self.calculate(material_type, dimensions)
                
                # Format response
                response_message = self.format_calculation_response(material_type, calculation_result, dimensions)
                
                return {
                    'message': response_message,
                    'data': {
                        'dimensions': dimensions,
                        'material_type': material_type,
                        'calculation_result': calculation_result,
                        'context_update': {
                            'calculator_dimensions': dimensions,
                            'calculator_material_type': material_type,
                            'calculator_state': 'complete',
                            'calculator_last_result': calculation_result
                        }
                    }
                }
        
        # Check if this is a message from the calculator
        calculator_pattern = re.search(r'I calculated materials for (?:an? )?(area|volume|length) of (\d+(?:\.\d+)?m)(?:\s+x\s+(\d+(?:\.\d+)?m))?(?:\s+x\s+(\d+(?:\.\d+)?m))?(?:\s+for\s+(\w+))?', message.strip().lower())
        if calculator_pattern:
            calc_type = calculator_pattern.group(1)
            dimensions_values = [d.replace('m', '') for d in calculator_pattern.groups()[1:4] if d]
            material = calculator_pattern.group(5) if len(calculator_pattern.groups()) > 4 else None
            
            # Map the calculation type to our material type
            if calc_type == 'area':
                material_type = 'area'
            elif calc_type == 'volume':
                material_type = 'volume'
            elif calc_type == 'length':
                material_type = 'linear'
            
            # Extract dimensions
            if len(dimensions_values) >= 1:
                dimensions['length'] = float(dimensions_values[0])
            if len(dimensions_values) >= 2:
                dimensions['width'] = float(dimensions_values[1])
            if len(dimensions_values) >= 3:
                dimensions['depth'] = float(dimensions_values[2])
            
            # Set material type if provided
            if material:
                dimensions['material'] = material
            
            # Mark as a follow-up from calculator
            dimensions['_from_calculator'] = True
            
            # If we have all required dimensions, generate a response
            if material_type and all(dim in dimensions for dim in self.required_dimensions.get(material_type, [])):
                # Calculate material quantities
                calculation_result = self.calculate(material_type, dimensions)
                
                # Format response
                response_message = f"I've received your calculation from the calculator. " + self.format_calculation_response(material_type, calculation_result, dimensions)
                
                return {
                    'message': response_message,
                    'data': {
                        'dimensions': dimensions,
                        'material_type': material_type,
                        'calculation_result': calculation_result,
                        'context_update': {
                            'calculator_dimensions': dimensions,
                            'calculator_material_type': material_type,
                            'calculator_state': 'complete',
                            'calculator_last_result': calculation_result
                        }
                    }
                }
        
        # Check if this is a simple numeric response (likely answering a previous question)
        if not current_material_type and len(current_dimensions) == 0 and re.match(r'^\d+(?:\.\d+)?\s*(?:m|meters|metre|metres|%|percent)?$', message.strip().lower()):
            # Extract the number
            match = re.match(r'^(\d+(?:\.\d+)?)\s*(?:m|meters|metre|metres|%|percent)?$', message.strip().lower())
            if match:
                value = float(match.group(1))
                
                # Check if this might be a wastage percentage
                if '%' in message or 'percent' in message.lower():
                    dimensions['wastage'] = value
                # Otherwise determine which dimension this might be based on what's missing
                elif 'length' not in dimensions:
                    dimensions['length'] = value
                elif 'width' not in dimensions and 'height' not in dimensions:
                    # Prefer width over height for most calculations
                    dimensions['width'] = value
                elif 'depth' not in dimensions and material_type == 'volume':
                    dimensions['depth'] = value
                elif 'height' not in dimensions:
                    dimensions['height'] = value
        
        # If we still couldn't detect the material type or have no dimensions
        if not material_type and not dimensions:
            return {
                'message': "I can help you calculate materials, but I need more information. "
                          "Please provide dimensions like length, width, and for volume calculations, depth. "
                          "For example: 'I need to paint a wall 5m long and 3m high' or "
                          "'Calculate concrete for a slab 4m x 4m x 0.15m deep'.",
                'data': {
                    'dimensions': dimensions,
                    'material_type': material_type,
                    'context_update': {
                        'calculator_dimensions': dimensions,
                        'calculator_material_type': material_type,
                        'calculator_state': 'awaiting_dimensions'
                    }
                }
            }
        
        # Check if we have the minimum required dimensions
        required_dimensions = {
            'area': ['length', 'width'],
            'volume': ['length', 'width', 'depth'],
            'linear': ['length']
        }
        
        # For area calculations, treat height as an alternative to width
        if material_type == 'area' and 'height' in dimensions and 'width' not in dimensions:
            dimensions['width'] = dimensions['height']
        
        missing_dimensions = [dim for dim in self.required_dimensions.get(material_type, []) if dim not in dimensions]
        
        if missing_dimensions:
            missing_str = ', '.join(missing_dimensions)
            return {
                'message': f"I need more information to calculate the materials. "
                          f"Please provide the {missing_str} of your project.",
                'data': {
                    'dimensions': dimensions,
                    'material_type': material_type,
                    'missing_dimensions': missing_dimensions,
                    'context_update': {
                        'calculator_dimensions': dimensions,
                        'calculator_material_type': material_type,
                        'calculator_state': 'awaiting_dimensions'
                    }
                }
            }
        
        # Calculate material quantities
        calculation_result = self.calculate(material_type, dimensions)
        
        # Format response
        response_message = self.format_calculation_response(material_type, calculation_result, dimensions)
        
        return {
            'message': response_message,
            'data': {
                'dimensions': dimensions,
                'material_type': material_type,
                'calculation_result': calculation_result,
                'context_update': {
                    'calculator_dimensions': dimensions,  # Keep dimensions for follow-up questions
                    'calculator_material_type': material_type,
                    'calculator_state': 'complete',
                    'calculator_last_result': calculation_result
                }
            }
        }
