const fs = require('fs');
const path = require('path');

const TEST_DIR = path.join(__dirname, '../data/test-dead-letter');

jest.mock('../src/utils/logger', () => ({
  info: jest.fn(),
  warn: jest.fn(),
  error: jest.fn(),
}));

let persistDeadLetter, listDeadLetters;

beforeAll(() => {
  process.env.DEAD_LETTER_DIR = TEST_DIR;
  const store = require('../src/utils/deadLetterStore');
  persistDeadLetter = store.persistDeadLetter;
  listDeadLetters = store.listDeadLetters;
});

afterAll(() => {
  delete process.env.DEAD_LETTER_DIR;
  if (fs.existsSync(TEST_DIR)) {
    fs.rmSync(TEST_DIR, { recursive: true });
  }
});

beforeEach(() => {
  if (fs.existsSync(TEST_DIR)) {
    fs.rmSync(TEST_DIR, { recursive: true });
  }
});

describe('DeadLetterStore', () => {
  describe('persistDeadLetter', () => {
    it('should create a JSON file for the failed job', () => {
      const jobData = {
        id: 42,
        data: { type: 'order_confirmation', payload: { order_id: 1 } },
        attemptsMade: 5,
      };
      const error = new Error('Delivery failed');

      const record = persistDeadLetter('email-notifications', jobData, error);

      expect(record.id).toBe(42);
      expect(record.queue).toBe('email-notifications');
      expect(record.type).toBe('order_confirmation');
      expect(record.error).toBe('Delivery failed');
      expect(record.attemptsMade).toBe(5);
      expect(record.failedAt).toBeDefined();

      const files = fs.readdirSync(TEST_DIR).filter(f => f.endsWith('.json'));
      expect(files.length).toBe(1);
    });

    it('should use Date.now() as id when job has no id', () => {
      const jobData = { data: { type: 'test' } };
      const error = new Error('fail');

      const record = persistDeadLetter('test-queue', jobData, error);

      expect(typeof record.id).toBe('number');
      expect(record.id).toBeGreaterThan(0);
    });

    it('should handle missing error message gracefully', () => {
      const jobData = { id: 1, data: { type: 'test' } };

      const record = persistDeadLetter('test-queue', jobData, null);

      expect(record.error).toBe('null');
    });

    it('should store the full payload', () => {
      const payload = { order_id: 99, amount: 5000 };
      const jobData = { id: 7, data: { type: 'order_webhook', ...payload } };
      const error = new Error('timeout');

      const record = persistDeadLetter('webhook-notifications', jobData, error);

      expect(record.payload).toEqual({ type: 'order_webhook', order_id: 99, amount: 5000 });
    });
  });

  describe('listDeadLetters', () => {
    it('should return empty array when no dead letters exist', () => {
      const result = listDeadLetters();
      expect(result).toEqual([]);
    });

    it('should return all persisted dead letters', () => {
      persistDeadLetter('queue-a', { id: 1, data: { type: 'a' } }, new Error('err1'));
      persistDeadLetter('queue-b', { id: 2, data: { type: 'b' } }, new Error('err2'));

      const result = listDeadLetters();

      expect(result).toHaveLength(2);
      expect(result.map(r => r.queue).sort()).toEqual(['queue-a', 'queue-b']);
    });

    it('should correctly parse persisted JSON', () => {
      persistDeadLetter('test', { id: 5, data: { type: 'x', foo: 'bar' } }, new Error('oops'));

      const result = listDeadLetters();

      expect(result[0].id).toBe(5);
      expect(result[0].type).toBe('x');
      expect(result[0].payload.foo).toBe('bar');
    });
  });
});
