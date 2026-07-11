const logger = require('../utils/logger');

/**
 * Simulate sending email notifications
 * In production, this would integrate with SendGrid, SES, or similar
 */
class EmailService {
  /**
   * Send order confirmation email
   */
  async sendOrderConfirmation(orderData) {
    logger.info(`[EMAIL] Sending order confirmation to ${orderData.attendee_email}`);
    
    // Simulate email sending delay
    await this._simulateDelay(100);
    
    // In production: await sendgrid.send({...})
    
    logger.info(`[EMAIL] Order confirmation sent successfully for order #${orderData.order_id}`);
    
    return {
      success: true,
      email: orderData.attendee_email,
      type: 'order_confirmation',
      order_id: orderData.order_id,
      timestamp: new Date().toISOString(),
    };
  }

  /**
   * Send event reminder email (24h before event)
   */
  async sendEventReminder(eventData) {
    logger.info(`[EMAIL] Sending event reminder for event #${eventData.event_id} to ${eventData.attendee_email}`);
    
    await this._simulateDelay(100);
    
    logger.info(`[EMAIL] Event reminder sent successfully for event #${eventData.event_id}`);
    
    return {
      success: true,
      email: eventData.attendee_email,
      type: 'event_reminder',
      event_id: eventData.event_id,
      event_name: eventData.event_name,
      event_date: eventData.event_date,
      timestamp: new Date().toISOString(),
    };
  }

  /**
   * Send payout notification to vendor
   */
  async sendPayoutNotification(payoutData) {
    logger.info(`[EMAIL] Sending payout notification to vendor #${payoutData.vendor_id}`);
    
    await this._simulateDelay(100);
    
    logger.info(`[EMAIL] Payout notification sent successfully for payout #${payoutData.payout_id}`);
    
    return {
      success: true,
      email: payoutData.vendor_email,
      type: 'payout_notification',
      payout_id: payoutData.payout_id,
      amount: payoutData.amount,
      currency: payoutData.currency,
      timestamp: new Date().toISOString(),
    };
  }

  /**
   * Send vendor approval notification
   */
  async sendVendorApprovalNotification(vendorData) {
    logger.info(`[EMAIL] Sending vendor approval notification to ${vendorData.vendor_email}`);
    
    await this._simulateDelay(100);
    
    logger.info(`[EMAIL] Vendor approval notification sent successfully for vendor #${vendorData.vendor_id}`);
    
    return {
      success: true,
      email: vendorData.vendor_email,
      type: 'vendor_approval',
      vendor_id: vendorData.vendor_id,
      status: vendorData.status,
      timestamp: new Date().toISOString(),
    };
  }

  /**
   * Simulate network delay for email sending
   */
  _simulateDelay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}

module.exports = new EmailService();
