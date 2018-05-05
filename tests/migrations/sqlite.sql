/**
 * SQLite
 */

DROP TABLE IF EXISTS "document";

CREATE TABLE "document" (
  "id"    INTEGER NOT NULL PRIMARY KEY,
  "title" TEXT    NOT NULL,
  "file"  TEXT    NOT NULL
);

DROP TABLE IF EXISTS "user";

CREATE TABLE "user" (
  "id"       INTEGER NOT NULL PRIMARY KEY,
  "nickname" TEXT    NOT NULL,
  "image"    TEXT    NOT NULL
);

DROP TABLE IF EXISTS "file";

CREATE TABLE "file" (
  "id"       INTEGER NOT NULL PRIMARY KEY,
  "year"     TEXT    NOT NULL,
  "file"     TEXT    NOT NULL
);
