-- Legacy schema, as found. Reproduced verbatim for the case study.
--
-- Notes on what is wrong here (see docs/FINDINGS.md):
--   * no foreign keys, no cascade rules
--   * no indexes on columns that are filtered and sorted on every request
--   * `password` sized for an MD5 digest (32 chars) — cannot hold a bcrypt hash
--   * `role` is a free-text VARCHAR
--   * latin1 collation on a site that serves Cyrillic content
--   * no created_at/updated_at convention

CREATE TABLE users (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  email    VARCHAR(255),
  password VARCHAR(32),
  role     VARCHAR(50)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE articles (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(255),
  body       TEXT,
  cover      VARCHAR(255),
  published  TINYINT DEFAULT 0,
  views      INT DEFAULT 0,
  created_at DATETIME
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE comments (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  article_id INT,
  author     VARCHAR(255),
  body       TEXT,
  created_at DATETIME
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
