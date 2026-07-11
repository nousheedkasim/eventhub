const axios = require('axios');
const logger = require('../utils/logger');

/**
 * Webhook delivery service with retry logic
 */
class WebhookService {
  /**
   * Deliver webhook to vendor endpoint
   */
  async deliverWebhook(webhookData) {
    const { url, payload, signature } = webhookData;
    const maxRetries = parseInt(process.env.WEBHOOK_MAX_RETRIES) || 5;
    const retryDelay = parseInt(process.env.WEBHOOK_RETRY_DELAY_MS) || 1000;
    const timeout = parseInt(process.env.WEBHOOK_TIMEOUT_MS) || 5000;

    let attempt = 0;
    let lastError = null;

    while (attempt < maxRetries) {
      attempt++;
      
      try {
        logger.info(`[WEBHOOK] Attempt ${attempt}/${maxRetries} to ${url}`);
        
        const response = await axios.post(url, payload, {
          headers: {
            'Content-Type': 'application/json',
            'X-EventHub-Signature': signature,
            'X-EventHub-Timestamp': new Date().toISOString(),
          },
          timeout: timeout,
        });

        logger.info(`[WEBHOOK] Successfully delivered webhook to ${url} (Status: ${response.status})`);
        
        return {
          success: true,
          status: response.status,
          attempt,
          timestamp: new Date().toISOString(),
        };
        
      } catch (error) {
        lastError = error;
        
        if (error.response) {
          // Server responded with error status
          logger.warn(`[WEBHOOK] Server responded with ${error.response.status} on attempt ${attempt}`);
          
          // Don't retry on client errors (4xx)
          if (error.response.status >= 400 && error.response.status < 500) {
            throw new Error(`Client error ${error.response.status}: ${error.response.data?.message || 'Unknown error'}`);
          }
        } else if (error.code === 'ECONNABORTED') {
          logger.warn(`[WEBHOOK] Timeout on attempt ${attempt}`);
        } else {
          logger.warn(`[WEBHOOK] Network error on attempt ${attempt}: ${error.message}`);
        }
        
        // Wait before retry (exponential backoff)
        if (attempt < maxRetries) {
          const delay = retryDelay * Math.pow(2, attempt - 1);
          logger.info(`[WEBHOOK] Retrying in ${delay}ms...`);
          await this._delay(delay);
        }
      }
    }

    // All retries exhausted
    logger.error(`[WEBHOOK] Failed to deliver webhook to ${url} after ${maxRetries} attempts`);
    
    throw new Error(`Webhook delivery failed after ${maxRetries} attempts: ${lastError?.message || 'Unknown error'}`);
  }

  /**
   * Deliver order status webhook to vendor
   */
  async deliverOrderWebhook(orderData) {
    const payload = {
      event: 'order.created',
      data: {
        order_id: orderData.order_id,
        vendor_id: orderData.vendor_id,
        total_amount: orderData.total_amount,
        currency: orderData.currency,
        status: orderData.status,
        created_at: orderData.created_at,
        items: orderData.items,
      },
    };

    const signature = this._generateSignature(payload);

    return this.deliverWebhook({
      url: orderData.vendor_webhook_url,
      payload,
      signature,
    });
  }

  /**
   * Deliver payout status webhook to vendor
   */
  async deliverPayoutWebhook(payoutData) {
    const payload = {
      event: 'payout.processed',
      data: {
        payout_id: payoutData.payout_id,
        vendor_id: payoutData.vendor_id,
        amount: payoutData.amount,
        currency: payoutData.currency,
        status: payoutData.status,
        processed_at: payoutData.processed_at,
      },
    };

    const signature = this._generateSignature(payload);

    return this.deliverWebhook({
      url: payoutData.vendor_webhook_url,
      payload,
      signature,
    });
  }

  /**
   * Generate HMAC signature for webhook payload
   */
  _generateSignature(payload) {
    const crypto = require('crypto');
    const secret = process.env.CORE_API_SECRET || 'secure_shared_secret';
    
    return crypto
      .createHmac('sha256', secret)
      .update(JSON.stringify(payload))
      .digest('hex');
  }

  /**
   * Delay helper
   */
  _delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}

module.exports = new WebhookService();
