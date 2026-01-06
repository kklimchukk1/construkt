"""
Store Information Handler
Provides answers about store locations, working hours, delivery, and contacts
"""
import json
import os
import re
from datetime import datetime

# Load store info
STORE_INFO_PATH = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), 'data', 'store_info.json')

def load_store_info():
    """Load store information from JSON file"""
    try:
        with open(STORE_INFO_PATH, 'r', encoding='utf-8') as f:
            return json.load(f)
    except Exception as e:
        print(f"Error loading store info: {e}")
        return None

# Keywords for detecting store info questions
HOURS_KEYWORDS = ['hour', 'time', 'open', 'close', 'work', 'schedule', 'business hours']
LOCATION_KEYWORDS = ['address', 'location', 'where', 'find', 'directions', 'located']
DELIVERY_KEYWORDS = ['delivery', 'deliver', 'shipping', 'ship', 'send', 'freight']
CONTACT_KEYWORDS = ['contact', 'phone', 'call', 'email', 'mail', 'reach']
PAYMENT_KEYWORDS = ['payment', 'pay', 'card', 'cash', 'credit', 'debit', 'checkout']

# City keywords (US cities)
CITY_KEYWORDS = {
    'new york': ['new york', 'nyc', 'manhattan'],
    'los angeles': ['los angeles', 'la', 'california'],
    'chicago': ['chicago', 'illinois']
}

def detect_store_intent(message):
    """Detect if message is about store information and what type"""
    message_lower = message.lower()

    intents = []

    if any(kw in message_lower for kw in HOURS_KEYWORDS):
        intents.append('hours')
    if any(kw in message_lower for kw in LOCATION_KEYWORDS):
        intents.append('location')
    if any(kw in message_lower for kw in DELIVERY_KEYWORDS):
        intents.append('delivery')
    if any(kw in message_lower for kw in CONTACT_KEYWORDS):
        intents.append('contact')
    if any(kw in message_lower for kw in PAYMENT_KEYWORDS):
        intents.append('payment')

    # Detect city
    city = None
    for city_id, keywords in CITY_KEYWORDS.items():
        if any(kw in message_lower for kw in keywords):
            city = city_id
            break

    return intents, city

def format_hours_response(store):
    """Format working hours response for a store"""
    hours = store['hours']

    response = f"**{store['city']}** ({store['address']})\n"
    response += f"Mon-Fri: {hours['weekdays']['open']} - {hours['weekdays']['close']}\n"
    response += f"Saturday: {hours['saturday']['open']} - {hours['saturday']['close']}\n"

    if hours['sunday']['open'] == 'closed':
        response += "Sunday: Closed"
    else:
        response += f"Sunday: {hours['sunday']['open']} - {hours['sunday']['close']}"

    return response

def get_current_status(store):
    """Check if store is currently open"""
    now = datetime.now()
    day = now.weekday()  # 0 = Monday, 6 = Sunday
    current_time = now.strftime("%H:%M")

    hours = store['hours']

    if day < 5:  # Weekday
        open_time = hours['weekdays']['open']
        close_time = hours['weekdays']['close']
    elif day == 5:  # Saturday
        open_time = hours['saturday']['open']
        close_time = hours['saturday']['close']
    else:  # Sunday
        if hours['sunday']['open'] == 'closed':
            return "closed", None, None
        open_time = hours['sunday']['open']
        close_time = hours['sunday']['close']

    if open_time <= current_time <= close_time:
        return "open", open_time, close_time
    else:
        return "closed", open_time, close_time

def handle_store_info(message):
    """Main handler for store information queries"""
    store_info = load_store_info()
    if not store_info:
        return None

    intents, city = detect_store_intent(message)

    if not intents:
        return None

    responses = []

    # Handle hours queries
    if 'hours' in intents:
        if city:
            # Specific city
            for store in store_info['stores']:
                if store['id'] == city:
                    status, open_time, close_time = get_current_status(store)
                    response = f"**{store['city']} Store Working Hours:**\n\n"
                    response += format_hours_response(store)

                    if status == "open":
                        response += f"\n\n*Currently OPEN (closes at {close_time})*"
                    else:
                        response += f"\n\n*Currently CLOSED*"

                    response += f"\n\n[View details on About page](/about#store-{city})"
                    responses.append(response)
                    break
        else:
            # All stores
            response = "**Our Store Working Hours:**\n\n"
            for store in store_info['stores']:
                response += format_hours_response(store) + "\n\n"
            response += "[View all store details](/about#locations)"
            responses.append(response)

    # Handle location queries
    if 'location' in intents:
        if city:
            for store in store_info['stores']:
                if store['id'] == city:
                    response = f"**{store['city']} Store Location:**\n\n"
                    response += f"Address: {store['address']}\n"
                    response += f"Phone: {store['phone']}\n"
                    response += f"Email: {store['email']}\n\n"
                    response += f"[View on map]({store.get('mapUrl', '#')})\n"
                    response += f"[More info](/about#store-{city})"
                    responses.append(response)
                    break
        else:
            response = "**Our Store Locations:**\n\n"
            for store in store_info['stores']:
                response += f"**{store['city']}:** {store['address']}\n"
                response += f"Phone: {store['phone']}\n\n"
            response += "[View all locations](/about#locations)"
            responses.append(response)

    # Handle delivery queries
    if 'delivery' in intents:
        response = "**Delivery Information:**\n\n"
        response += "| Zone | Min. Order | Cost | Free From |\n"
        response += "|------|------------|------|----------|\n"
        for zone in store_info['delivery']['zones']:
            response += f"| {zone['name']} | ${zone['min_order']} | ${zone['cost']} | ${zone['free_from']} |\n"

        response += "\n**Notes:**\n"
        for note in store_info['delivery']['notes'][:2]:
            response += f"- {note}\n"

        response += "\n[Full delivery info](/about#delivery)"
        responses.append(response)

    # Handle contact queries
    if 'contact' in intents:
        contacts = store_info['contacts']
        response = "**Contact Information:**\n\n"
        response += f"General Phone: {contacts['general_phone']} (free)\n"
        response += f"Email: {contacts['general_email']}\n"
        response += f"Support: {contacts['support_email']}\n\n"
        response += "[All contacts](/about#contact)"
        responses.append(response)

    # Handle payment queries
    if 'payment' in intents:
        response = "**Accepted Payment Methods:**\n\n"
        for method in store_info['payment_methods']:
            response += f"- {method['name_en']}\n"
        response += "\n[More info](/about#payment)"
        responses.append(response)

    if responses:
        return "\n\n---\n\n".join(responses)

    return None

def is_store_info_query(message):
    """Check if message is about store information"""
    intents, _ = detect_store_intent(message)
    return len(intents) > 0
