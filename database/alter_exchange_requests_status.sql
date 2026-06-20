ALTER TABLE exchange_requests
MODIFY COLUMN status ENUM(
  'awaiting_approval',
  'pending',
  'negotiating',
  'accepted',
  'rejected'
) NOT NULL DEFAULT 'awaiting_approval';
