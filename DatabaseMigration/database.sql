CREATE TABLE IF NOT EXISTS articles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  url VARCHAR(512) NOT NULL,
  title TEXT NULL,
  description TEXT NULL,
  content MEDIUMTEXT NULL,
  source_name VARCHAR(191) NULL,
  image_url VARCHAR(512) NULL,
  published_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_url (url),
  KEY idx_published_at (published_at),
  KEY idx_source (source_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE articles
ADD FULLTEXT KEY ft_title_desc_content (title, description, content);

ALTER TABLE articles
  ADD COLUMN source_id VARCHAR(64) NULL AFTER url,
  ADD COLUMN country VARCHAR(8) NULL AFTER source_name;

CREATE INDEX idx_articles_source_id ON articles (source_id);
CREATE INDEX idx_articles_country ON articles (country);


CREATE TABLE IF NOT EXISTS sources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_id VARCHAR(64) NOT NULL,           
  name VARCHAR(191) NOT NULL,           
  description TEXT NULL,
  url VARCHAR(512) NULL,
  category VARCHAR(64) NULL,
  language VARCHAR(8) NULL,
  country VARCHAR(8) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_source_id (source_id),
  KEY idx_country (country),
  KEY idx_language (language),
  KEY idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id INT UNSIGNED NOT NULL,
  author VARCHAR(80) NULL,
  body_raw MEDIUMTEXT NOT NULL,
  ip_hash VARBINARY(32) NULL,
  ua VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_article (article_id, created_at),
  CONSTRAINT fk_comments_article
    FOREIGN KEY (article_id) REFERENCES articles(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
