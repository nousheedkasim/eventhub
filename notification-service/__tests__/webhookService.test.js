const axios = require('axios');
const webhookService = require('../src/services/webhookService');

jest.mock('axios');
jest.mock('../src/utils/logger', () => ({
  info: jest.fn(),
  warn: jest.fn(),
  error: jest.fn(),
}));

describe('WebhookService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    process.env.WEBHOOK_MAX_RETRIES = '3';
    process.env.WEBHOOK_RETRY_DELAY_MS = '10';
    process.env.WEBHOOK_TIMEOUT_MS = '5000';
    jest.spyOn(webhookService, '_delay').mockResolvedValue();
  });

  afterEach(() => {
    delete process.env.WEBHOOK_MAX_RETRIES;
    delete process.env.WEBHOOK_RETRY_DELAY_MS;
    delete process.env.WEBHOOK_TIMEOUT_MS;
  });

  describe('deliverWebhook', () => {
    it('should succeed on first attempt', async () => {
      axios.post.mockResolvedValue({ status: 200 });

      const result = await webhookService.deliverWebhook({
        url: 'https://vendor.example.com/webhook',
        payload: { event: 'test' },
        signature: 'abc123',
      });

      expect(result.success).toBe(true);
      expect(result.status).toBe(200);
      expect(result.attempt).toBe(1);
      expect(axios.post).toHaveBeenCalledTimes(1);
    });

    it('should retry on 5xx error and eventually succeed', async () => {
      axios.post
        .mockRejectedValueOnce({ response: { status: 500 } })
        .mockRejectedValueOnce({ response: { status: 503 } })
        .mockResolvedValue({ status: 200 });

      const result = await webhookService.deliverWebhook({
        url: 'https://vendor.example.com/webhook',
        payload: { event: 'test' },
        signature: 'abc123',
      });

      expect(result.success).toBe(true);
      expect(result.attempt).toBe(3);
      expect(axios.post).toHaveBeenCalledTimes(3);
      expect(webhookService._delay).toHaveBeenCalledTimes(2);
    });

    it('should NOT retry on 4xx client error', async () => {
      axios.post.mockRejectedValue({
        response: { status: 404, data: { message: 'Not found' } },
      });

      await expect(
        webhookService.deliverWebhook({
          url: 'https://vendor.example.com/webhook',
          payload: { event: 'test' },
          signature: 'abc123',
        })
      ).rejects.toThrow('Client error 404: Not found');

      expect(axios.post).toHaveBeenCalledTimes(1);
    });

    it('should throw after exhausting all retries on 5xx', async () => {
      axios.post.mockRejectedValue({ response: { status: 500 } });

      await expect(
        webhookService.deliverWebhook({
          url: 'https://vendor.example.com/webhook',
          payload: { event: 'test' },
          signature: 'abc123',
        })
      ).rejects.toThrow('Webhook delivery failed after 3 attempts');

      expect(axios.post).toHaveBeenCalledTimes(3);
    });

    it('should retry on timeout (ECONNABORTED)', async () => {
      axios.post
        .mockRejectedValueOnce({ code: 'ECONNABORTED' })
        .mockResolvedValue({ status: 200 });

      const result = await webhookService.deliverWebhook({
        url: 'https://vendor.example.com/webhook',
        payload: { event: 'test' },
        signature: 'abc123',
      });

      expect(result.success).toBe(true);
      expect(result.attempt).toBe(2);
    });

    it('should retry on network error', async () => {
      axios.post
        .mockRejectedValueOnce({ message: 'connect ECONNREFUSED' })
        .mockResolvedValue({ status: 200 });

      const result = await webhookService.deliverWebhook({
        url: 'https://vendor.example.com/webhook',
        payload: { event: 'test' },
        signature: 'abc123',
      });

      expect(result.success).toBe(true);
      expect(result.attempt).toBe(2);
    });
  });

  describe('deliverOrderWebhook', () => {
    it('should wrap order data in correct payload shape', async () => {
      axios.post.mockResolvedValue({ status: 200 });

      await webhookService.deliverOrderWebhook({
        order_id: 101,
        vendor_id: 5,
        total_amount: 9999,
        currency: 'AED',
        status: 'confirmed',
        created_at: '2026-07-11T10:00:00Z',
        items: [],
        vendor_webhook_url: 'https://vendor.example.com/webhook',
      });

      expect(axios.post).toHaveBeenCalledTimes(1);
      const [url, payload] = axios.post.mock.calls[0];
      expect(url).toBe('https://vendor.example.com/webhook');
      expect(payload.event).toBe('order.created');
      expect(payload.data.order_id).toBe(101);
      expect(payload.data.vendor_id).toBe(5);
      expect(payload.data.total_amount).toBe(9999);
    });
  });

  describe('deliverPayoutWebhook', () => {
    it('should wrap payout data in correct payload shape', async () => {
      axios.post.mockResolvedValue({ status: 200 });

      await webhookService.deliverPayoutWebhook({
        payout_id: 301,
        vendor_id: 5,
        amount: 8999,
        currency: 'AED',
        status: 'completed',
        processed_at: '2026-07-11T12:00:00Z',
        vendor_webhook_url: 'https://vendor.example.com/webhook',
      });

      expect(axios.post).toHaveBeenCalledTimes(1);
      const [url, payload] = axios.post.mock.calls[0];
      expect(url).toBe('https://vendor.example.com/webhook');
      expect(payload.event).toBe('payout.processed');
      expect(payload.data.payout_id).toBe(301);
      expect(payload.data.amount).toBe(8999);
    });
  });

  describe('_generateSignature', () => {
    it('should generate consistent HMAC-SHA256 signatures', () => {
      const payload = { event: 'test', data: { id: 1 } };
      const sig1 = webhookService._generateSignature(payload);
      const sig2 = webhookService._generateSignature(payload);

      expect(sig1).toBe(sig2);
      expect(typeof sig1).toBe('string');
      expect(sig1).toHaveLength(64);
    });
  });
});
