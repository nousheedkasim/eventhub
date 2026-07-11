const { processWebhookJob } = require('../src/workers/webhookWorker');
const webhookService = require('../src/services/webhookService');

jest.mock('../src/services/webhookService', () => ({
  deliverOrderWebhook: jest.fn(),
  deliverPayoutWebhook: jest.fn(),
}));

jest.mock('../src/utils/logger', () => ({
  info: jest.fn(),
  warn: jest.fn(),
  error: jest.fn(),
}));

describe('WebhookWorker', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  const makeJob = (type, data) => ({ id: 1, data: { type, data } });

  it('should process order_webhook jobs', async () => {
    const data = { order_id: 101, vendor_webhook_url: 'https://example.com/hook' };
    webhookService.deliverOrderWebhook.mockResolvedValue({ success: true });

    const result = await processWebhookJob(makeJob('order_webhook', data));

    expect(result.success).toBe(true);
    expect(webhookService.deliverOrderWebhook).toHaveBeenCalledWith(data);
  });

  it('should process payout_webhook jobs', async () => {
    const data = { payout_id: 301, vendor_webhook_url: 'https://example.com/hook' };
    webhookService.deliverPayoutWebhook.mockResolvedValue({ success: true });

    const result = await processWebhookJob(makeJob('payout_webhook', data));

    expect(result.success).toBe(true);
    expect(webhookService.deliverPayoutWebhook).toHaveBeenCalledWith(data);
  });

  it('should throw on unknown webhook type', async () => {
    await expect(processWebhookJob(makeJob('unknown_type', {}))).rejects.toThrow('Unknown webhook type: unknown_type');
  });

  it('should propagate service errors', async () => {
    webhookService.deliverOrderWebhook.mockRejectedValue(new Error('Delivery failed'));

    await expect(processWebhookJob(makeJob('order_webhook', { order_id: 1 }))).rejects.toThrow('Delivery failed');
  });
});
