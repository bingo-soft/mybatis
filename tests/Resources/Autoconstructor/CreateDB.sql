DROP TABLE IF EXISTS subject;

DROP TABLE IF EXISTS extensive_subject;

CREATE TABLE subject (
  id     INT NOT NULL,
  name   VARCHAR(20),
  age    INT NOT NULL,
  height INT,
  weight INT,
  active BIT,
  dt     TIMESTAMP
);

CREATE TABLE extensive_subject (
  aByte      SMALLINT,
  aShort     SMALLINT,
  aChar      CHAR,
  anInt      INT,
  aLong      BIGINT,
  aFloat     FLOAT,
  aDouble    FLOAT,
  aBoolean   BIT,
  aString    VARCHAR(255),
  anEnum     VARCHAR(50),
  aClob      TEXT,
  aBlob      TEXT,
  aTimestamp TIMESTAMP
);

INSERT INTO subject VALUES
  (1, 'a', 10, 100, 45, b'1', CURRENT_TIMESTAMP),
  (2, 'b', 10, NULL, 45, b'1', CURRENT_TIMESTAMP),
  (2, 'c', 10, NULL, NULL, b'0', CURRENT_TIMESTAMP);

INSERT INTO extensive_subject
VALUES
  (1, 1, 'a', 1, 1, 1, 1.0, b'1', 'a', 'AVALUE', 'ACLOB', 'aaaaaabbbbbb', CURRENT_TIMESTAMP),
  (2, 2, 'b', 2, 2, 2, 2.0, b'1', 'b', 'BVALUE', 'BCLOB', '010101010101', CURRENT_TIMESTAMP),
  (3, 3, 'c', 3, 3, 3, 3.0, b'0', 'c', 'CVALUE', 'CCLOB', '777d010078da', CURRENT_TIMESTAMP);