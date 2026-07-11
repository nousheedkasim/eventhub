const { processEmailJob } = require('../src/workers/emailWorker');
const emailService = require('../src/services/emailService');

jest.mock('../src/services/emailService', () => ({
  sendOrderConfirmation: jest.fn(),
  sendEventReminder: jest.fn(),
  sendPayoutNotification: jest.fn(),
  sendVendorApprovalNotification: jest.fn(),
}));

jest.mock('../src/utils/logger', () => ({
  info: jest.fn(),
  warn: jest.fn(),
  error: jest.fn(),
}));

describe('EmailWorker', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  const makeJob = (type, data) => ({ id: 1, data: { type, data } });

  it('should process order_confirmation jobs', async () => {
    const data = { order_id: 101, attendee_email: 'test@test.com' };
    emailService.sendOrderConfirmation.mockResolvedValue({ success: true });

    const result = await processEmailJob(makeJob('order_confirmation', data));

    expect(result.success).toBe(true);
    expect(emailService.sendOrderConfirmation).toHaveBeenCalledWith(data);
  });

  it('should process event_reminder jobs', async () => {
    const data = { event_id: 201, attendee_email: 'test@test.com' };
    emailService.sendEventReminder.mockResolvedValue({ success: true });

    const result = await processEmailJob(makeJob('event_reminder', data));

    expect(result.success).toBe(true);
    expect(emailService.sendEventReminder).toHaveBeenCalledWith(data);
  });

  it('should process payout_notification jobs', async () => {
    const data = { payout_id: 301, vendor_email: 'vendor@test.com' };
    emailService.sendPayoutNotification.mockResolvedValue({ success: true });

    const result = await processEmailJob(makeJob('payout_notification', data));

    expect(result.success).toBe(true);
    expect(emailService.sendPayoutNotification).toHaveBeenCalledWith(data);
  });

  it('should process vendor_approval jobs', async () => {
    const data = { vendor_id: 10, vendor_email: 'vendor@test.com', status: 'approved' };
    emailService.sendVendorApprovalNotification.mockResolvedValue({ success: true });

    const result = await processEmailJob(makeJob('vendor_approval', data));

    expect(result.success).toBe(true);
    expect(emailService.sendVendorApprovalNotification).toHaveBeenCalledWith(data);
  });

  it('should throw on unknown email type', async () => {
    await expect(processEmailJob(makeJob('unknown_type', {}))).rejects.toThrow('Unknown email type: unknown_type');
  });

  it('should propagate service errors', async () => {
    emailService.sendOrderConfirmation.mockRejectedValue(new Error('Service down'));

    await expect(processEmailJob(makeJob('order_confirmation', { order_id: 1 }))).rejects.toThrow('Service down');
  });
});
