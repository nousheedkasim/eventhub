const emailService = require('../src/services/emailService');

jest.mock('../src/utils/logger', () => ({
  info: jest.fn(),
  warn: jest.fn(),
  error: jest.fn(),
}));

describe('EmailService', () => {
  beforeEach(() => {
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  describe('sendOrderConfirmation', () => {
    it('should return success with correct fields', async () => {
      const data = {
        order_id: 101,
        attendee_email: 'attendee@example.com',
      };

      const promise = emailService.sendOrderConfirmation(data);
      jest.advanceTimersByTime(100);
      const result = await promise;

      expect(result.success).toBe(true);
      expect(result.email).toBe('attendee@example.com');
      expect(result.type).toBe('order_confirmation');
      expect(result.order_id).toBe(101);
      expect(result.timestamp).toBeDefined();
    });
  });

  describe('sendEventReminder', () => {
    it('should return success with event details', async () => {
      const data = {
        event_id: 201,
        attendee_email: 'user@test.com',
        event_name: 'Summer Festival',
        event_date: '2026-08-20T18:00:00Z',
      };

      const promise = emailService.sendEventReminder(data);
      jest.advanceTimersByTime(100);
      const result = await promise;

      expect(result.success).toBe(true);
      expect(result.email).toBe('user@test.com');
      expect(result.type).toBe('event_reminder');
      expect(result.event_id).toBe(201);
      expect(result.event_name).toBe('Summer Festival');
      expect(result.event_date).toBe('2026-08-20T18:00:00Z');
      expect(result.timestamp).toBeDefined();
    });
  });

  describe('sendPayoutNotification', () => {
    it('should return success with payout details', async () => {
      const data = {
        payout_id: 301,
        vendor_id: 5,
        vendor_email: 'vendor@test.com',
        amount: 9999,
        currency: 'AED',
      };

      const promise = emailService.sendPayoutNotification(data);
      jest.advanceTimersByTime(100);
      const result = await promise;

      expect(result.success).toBe(true);
      expect(result.email).toBe('vendor@test.com');
      expect(result.type).toBe('payout_notification');
      expect(result.payout_id).toBe(301);
      expect(result.amount).toBe(9999);
      expect(result.currency).toBe('AED');
      expect(result.timestamp).toBeDefined();
    });
  });

  describe('sendVendorApprovalNotification', () => {
    it('should return success with vendor status', async () => {
      const data = {
        vendor_id: 10,
        vendor_email: 'newvendor@test.com',
        status: 'approved',
      };

      const promise = emailService.sendVendorApprovalNotification(data);
      jest.advanceTimersByTime(100);
      const result = await promise;

      expect(result.success).toBe(true);
      expect(result.email).toBe('newvendor@test.com');
      expect(result.type).toBe('vendor_approval');
      expect(result.vendor_id).toBe(10);
      expect(result.status).toBe('approved');
      expect(result.timestamp).toBeDefined();
    });

    it('should handle rejected status', async () => {
      const data = {
        vendor_id: 11,
        vendor_email: 'rejected@test.com',
        status: 'rejected',
      };

      const promise = emailService.sendVendorApprovalNotification(data);
      jest.advanceTimersByTime(100);
      const result = await promise;

      expect(result.success).toBe(true);
      expect(result.status).toBe('rejected');
    });
  });
});
