const request = require('supertest');
const express = require('express');
const { listDeadLetters } = require('../src/utils/deadLetterStore');

jest.mock('../src/utils/logger', () => ({
  info: jest.fn(),
  warn: jest.fn(),
  error: jest.fn(),
}));

jest.mock('../src/utils/deadLetterStore', () => ({
  listDeadLetters: jest.fn(),
}));

const app = express();
app.use(express.json());

app.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    service: 'notification-service',
    timestamp: new Date().toISOString(),
  });
});

app.get('/dead-letter', (req, res) => {
  const deadLetters = listDeadLetters();
  res.json({
    count: deadLetters.length,
    jobs: deadLetters,
  });
});

describe('Server HTTP Endpoints', () => {
  describe('GET /health', () => {
    it('should return 200 with healthy status', async () => {
      const res = await request(app).get('/health');

      expect(res.status).toBe(200);
      expect(res.body.status).toBe('healthy');
      expect(res.body.service).toBe('notification-service');
      expect(res.body.timestamp).toBeDefined();
    });
  });

  describe('GET /dead-letter', () => {
    it('should return 200 with count and jobs', async () => {
      listDeadLetters.mockReturnValue([
        { id: 1, queue: 'email-notifications', error: 'fail' },
      ]);

      const res = await request(app).get('/dead-letter');

      expect(res.status).toBe(200);
      expect(res.body.count).toBe(1);
      expect(res.body.jobs).toHaveLength(1);
      expect(res.body.jobs[0].id).toBe(1);
    });

    it('should return empty list when no dead letters', async () => {
      listDeadLetters.mockReturnValue([]);

      const res = await request(app).get('/dead-letter');

      expect(res.status).toBe(200);
      expect(res.body.count).toBe(0);
      expect(res.body.jobs).toEqual([]);
    });
  });
});
