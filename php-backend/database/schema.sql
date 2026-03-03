CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_super_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS workspaces (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  owner_user_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_workspaces_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS workspace_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  role VARCHAR(40) NOT NULL DEFAULT 'agent',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_workspace_user (workspace_id, user_id),
  CONSTRAINT fk_workspace_users_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_workspace_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contacts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(190) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(50) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_contacts_ws_email (workspace_id, email),
  CONSTRAINT fk_contacts_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS channels (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  provider VARCHAR(60) NOT NULL,
  account_label VARCHAR(190) NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'connected',
  settings JSON NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_channels_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS conversations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  contact_id BIGINT UNSIGNED NULL,
  channel VARCHAR(60) NOT NULL DEFAULT 'gmail',
  status VARCHAR(40) NOT NULL DEFAULT 'open',
  priority VARCHAR(40) NOT NULL DEFAULT 'normal',
  last_message_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_conversations_ws (workspace_id),
  CONSTRAINT fk_conversations_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_conversations_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  conversation_id BIGINT UNSIGNED NOT NULL,
  direction VARCHAR(20) NOT NULL DEFAULT 'in',
  sender VARCHAR(190) NULL,
  body TEXT NOT NULL,
  sent_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_messages_conv (conversation_id),
  CONSTRAINT fk_messages_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_messages_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
);

INSERT INTO users (name, email, password_hash, is_super_admin)
SELECT 'MOVOER Admin', 'admin@movoer.test', '$2y$12$Fv0eQuWJKBwRDbgMaC/jIe8bCkrYPoNV/QasajJU.jB4L9ltRunvu', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='admin@movoer.test');

INSERT INTO users (name, email, password_hash, is_super_admin)
SELECT 'MOVOER Owner', 'owner@movoer.test', '$2y$12$k9alOST9szMkDZPuIbu9a.qgAuEb.pWk58NfhmONh/COkAr5ECVKW', 0
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='owner@movoer.test');


INSERT INTO workspaces (name, slug, owner_user_id)
SELECT 'MOVOER Demo Workspace', 'movoer-demo', u.id FROM users u
WHERE u.email='owner@movoer.test' AND NOT EXISTS (SELECT 1 FROM workspaces WHERE slug='movoer-demo');

INSERT INTO workspace_users (workspace_id, user_id, role)
SELECT w.id, u.id, 'owner' FROM workspaces w
JOIN users u ON u.email='owner@movoer.test'
WHERE w.slug='movoer-demo'
AND NOT EXISTS (SELECT 1 FROM workspace_users wu WHERE wu.workspace_id=w.id AND wu.user_id=u.id);

INSERT INTO contacts (workspace_id, name, email)
SELECT w.id, 'Client One', 'client1@example.com' FROM workspaces w
WHERE w.slug='movoer-demo'
AND NOT EXISTS (SELECT 1 FROM contacts c WHERE c.workspace_id=w.id AND c.email='client1@example.com');

INSERT INTO conversations (workspace_id, contact_id, channel, status, priority, last_message_at)
SELECT w.id, c.id, 'gmail', 'open', 'normal', NOW() FROM workspaces w
JOIN contacts c ON c.workspace_id=w.id AND c.email='client1@example.com'
WHERE w.slug='movoer-demo'
AND NOT EXISTS (SELECT 1 FROM conversations v WHERE v.workspace_id=w.id AND v.contact_id=c.id);

INSERT INTO messages (workspace_id, conversation_id, direction, sender, body, sent_at)
SELECT w.id, v.id, 'in', 'client1@example.com', 'Hi, I need help with my order.', NOW()
FROM workspaces w JOIN conversations v ON v.workspace_id=w.id
WHERE w.slug='movoer-demo'
AND NOT EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id=v.id);
