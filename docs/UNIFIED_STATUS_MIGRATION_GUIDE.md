# Unified Order Status System Migration Guide

## Overview

The unified order status system consolidates the previously separate order_status_id, crop_status_id, and fulfillment_status_id into a single unified_status_id that tracks the complete lifecycle of an order.

## Key Components

### 1. StatusTransitionService (`app/Services/StatusTransitionService.php`)
- Validates status transitions based on business rules
- Performs status transitions with logging
- Handles bulk status updates
- Manages automatic status updates based on business events

### 2. Event-Driven Status Updates
The system uses events to automatically update order statuses:
- **OrderCropPlanted**: Fired when a crop is planted
- **AllCropsReady**: Fired when all crops are ready to harvest
- **OrderHarvested**: Fired when all crops are harvested
- **OrderPacked**: Fired when order status changes to packing
- **PaymentReceived**: Fired when a payment is marked as completed

### 3. Observers
- **OrderStatusObserver**: Handles unified status changes and maintains backward compatibility
- **CropObserver**: Triggers crop-related events
- **PaymentObserver**: Triggers payment-related events

## Usage Examples

### Manual Status Transition
```php
// Using the Order model helper method
$result = $order->transitionTo('packing', [
    'notes' => 'All items harvested and ready for packing'
]);

if ($result['success']) {
    // Status updated successfully
}

// Check if transition is allowed
if ($order->canTransitionTo('ready_for_delivery')) {
    // Transition is valid
}
```

### Using the Service Directly
```php
$statusService = app(StatusTransitionService::class);

// Get valid next statuses
$validStatuses = $statusService->getValidNextStatuses($order);

// Perform transition
$result = $statusService->transitionTo($order, 'confirmed', [
    'manual' => true,
    'user_id' => auth()->id()
]);

// Bulk update
$result = $statusService->bulkTransition(
    [$order1->id, $order2->id], 
    'growing',
    ['reason' => 'Crops planted']
);
```

### Automatic Status Updates
Status updates happen automatically based on business events:
```php
// When a crop is planted
$crop->update(['planting_at' => now()]); // Triggers OrderCropPlanted event

// When payment is received
$payment->markAsCompleted(); // Triggers PaymentReceived event
```

## Business Rules

### Orders with Crops
- Cannot skip production stages (must go through growing → ready_to_harvest → harvesting)
- Cannot move to packing until all crops are harvested
- Status automatically updates as crops progress

### Orders without Crops
- Can go directly from confirmed to packing
- Don't require production stage transitions

### Payment Requirements
- Orders requiring immediate invoicing cannot move to ready_for_delivery until paid
- B2B orders with deferred billing can proceed without immediate payment

### Template Orders
- Limited transitions (can only be cancelled)
- Generated orders start with pending status

### Final Statuses
- Delivered and cancelled are final statuses
- No transitions allowed from final statuses

## Backward Compatibility

The system maintains backward compatibility by:
1. Automatically syncing legacy status fields when unified status changes
2. The `syncUnifiedStatus()` method maps old statuses to unified status
3. Legacy fields (order_status_id, crop_status_id, fulfillment_status_id) are still updated

## UI Integration

### Filament Resource Actions
- **Change Status**: Single order status transition with validation
- **Bulk Update Status**: Update multiple orders at once
- Status badge displays unified status with stage information
- Valid transitions shown in dropdown based on current status

## Migration Checklist

When updating existing code:
1. Replace direct status field updates with `transitionTo()` method
2. Use StatusTransitionService for complex status logic
3. Listen for status change events instead of checking status fields
4. Use unified_status_id for new features
5. Consider business rules when implementing status changes

## Status Stages

1. **Pre-Production**: draft, pending, confirmed
2. **Production**: growing, ready_to_harvest, harvesting
3. **Fulfillment**: packing, ready_for_delivery, out_for_delivery
4. **Final**: delivered, cancelled, template

## Best Practices

1. Always use the transition service for status updates
2. Include context information when transitioning
3. Check if transition is valid before attempting
4. Log important status changes with context
5. Handle failed transitions gracefully
6. Use events for reactive status updates
7. Maintain backward compatibility until full migration