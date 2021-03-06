/*******************************************************************************
Create Date:    2022-06-15
Author:         Paul-Adrien Penet <pauladrienpenet@gmail.com>
Author:         Youn Mélois <youn@melois.dev>
Description:    Creates the database tables and relations.
Usage:          psql -U postgres -d matchmaking -a -f model.sql
                https://stackoverflow.com/a/23992045/12619942
*******************************************************************************/

TRUNCATE TABLE "sport" CASCADE;
TRUNCATE TABLE "city" CASCADE;
TRUNCATE TABLE "user" CASCADE;
TRUNCATE TABLE "user_level" CASCADE;
TRUNCATE TABLE "match" CASCADE;
TRUNCATE TABLE "participation" CASCADE;
TRUNCATE TABLE "team" CASCADE;
TRUNCATE TABLE "note" CASCADE;
TRUNCATE TABLE "notification" CASCADE;

DROP TABLE IF EXISTS "sport" CASCADE;
DROP TABLE IF EXISTS "city" CASCADE;
DROP TABLE IF EXISTS "user" CASCADE;
DROP TABLE IF EXISTS "user_level" CASCADE;
DROP TABLE IF EXISTS "match" CASCADE;
DROP TABLE IF EXISTS "participation" CASCADE;
DROP TABLE IF EXISTS "team" CASCADE;
DROP TABLE IF EXISTS "note" CASCADE;
DROP TABLE IF EXISTS "notification" CASCADE;

-- Table sport
CREATE TABLE "sport"(
    "id" SERIAL PRIMARY KEY,
    "name_id" VARCHAR(64) UNIQUE NOT NULL,
    "default_max_players" INTEGER,
    "default_min_players" INTEGER
);

-- Table city
CREATE TABLE "city"(
    "id" SERIAL PRIMARY KEY,
    "name" VARCHAR(64) NOT NULL,
    "postal_code" VARCHAR(10) NOT NULL,
    "department_name" VARCHAR(64) NOT NULL,
    "department_code" VARCHAR(10) NOT NULL,
    "region_name" VARCHAR(64) NOT NULL,
    "region_code" VARCHAR(10) NOT NULL,
    "latitude" DOUBLE PRECISION NOT NULL,
    "longitude" DOUBLE PRECISION NOT NULL
);

-- Table users
CREATE TABLE "user"(
    "id" SERIAL PRIMARY KEY,
    "city_id" INTEGER,
    "first_name" VARCHAR(64) NOT NULL,
    "last_name" VARCHAR(64) NOT NULL,
    "email" VARCHAR(64) UNIQUE NOT NULL,
    "phone_number" VARCHAR(30),
    "password_hash" VARCHAR(60) NOT NULL,
    "profile_picture_url" VARCHAR(255),
    "birthdate" DATE NOT NULL,
    "access_token" VARCHAR(64),

		FOREIGN KEY("city_id") REFERENCES "city"("id")
			ON UPDATE CASCADE ON DELETE CASCADE
);
COMMENT ON COLUMN "user"."phone_number" 
	IS 'Google says to never store phone numbers as numeric data';
COMMENT ON COLUMN "user"."password_hash" 
	IS 'use PASSWORD_BCRYPT algo';

-- Table user_level
CREATE TABLE "user_level"(
    "id" SERIAL PRIMARY KEY,
    "user_id" INTEGER NOT NULL,
    "sport_id" INTEGER NOT NULL,
    "level" SMALLINT NOT NULL,
    "description" TEXT NOT NULL,

		FOREIGN KEY("user_id") REFERENCES "user"("id")
			ON UPDATE CASCADE ON DELETE CASCADE,
		FOREIGN KEY("sport_id") REFERENCES "sport"("id")
			ON UPDATE CASCADE ON DELETE CASCADE
);

-- Table match
CREATE TABLE "match"(
    "id" SERIAL PRIMARY KEY,
    "organizer_id" INTEGER NOT NULL,
    "sport_id" INTEGER NOT NULL,
    "latitude" DOUBLE PRECISION NOT NULL,
    "longitude" DOUBLE PRECISION NOT NULL,
    "max_players" INTEGER NOT NULL,
    "min_players" INTEGER NOT NULL,
    "price" DOUBLE PRECISION,
    "duration" TIME(0) WITHOUT TIME ZONE NOT NULL,
    "datetime" TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    "description" TEXT NOT NULL,
    "recommended_level" INTEGER NOT NULL,

		FOREIGN KEY("organizer_id") REFERENCES "user"("id")
			ON UPDATE CASCADE ON DELETE CASCADE,
		FOREIGN KEY("sport_id") REFERENCES "sport"("id")
			ON UPDATE CASCADE ON DELETE CASCADE
);

-- Table team
CREATE TABLE "team"(
    "id" SERIAL PRIMARY KEY,
    "name" VARCHAR(64),
    "match_id" INTEGER NOT NULL,

		FOREIGN KEY("match_id") REFERENCES "match"("id")
			ON UPDATE CASCADE ON DELETE CASCADE
);
COMMENT ON COLUMN "team"."name"
	IS 'by default : team 1, team 2...';
ALTER TABLE "team" ADD CONSTRAINT uq_name_match_id UNIQUE("name", "match_id");

-- Table participation
CREATE TABLE "participation"(
    "id" SERIAL PRIMARY KEY,
    "user_id" INTEGER NOT NULL,
    "match_id" INTEGER NOT NULL,
    "team_id" INTEGER,
    "validation" BOOLEAN NOT NULL,
    "score" INTEGER NOT NULL,

		FOREIGN KEY("user_id") REFERENCES "user"("id")
			ON UPDATE CASCADE ON DELETE CASCADE,
		FOREIGN KEY("match_id") REFERENCES "match"("id")
			ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY("team_id") REFERENCES "team"("id")
			ON UPDATE CASCADE ON DELETE CASCADE
);

-- Table note
CREATE TABLE "note"(
    "id" SERIAL PRIMARY KEY,
    "score" SMALLINT NOT NULL,
    "comment" TEXT NOT NULL,
    "user_id" INTEGER UNIQUE NOT NULL,

		FOREIGN KEY("user_id") REFERENCES "user"("id")
			ON UPDATE CASCADE ON DELETE CASCADE
);

-- Table notification
CREATE TABLE "notification"(
    "id" SERIAL PRIMARY KEY,
    "user_id" INTEGER NOT NULL,
    "message" TEXT NOT NULL,
    "url" TEXT NOT NULL,

		FOREIGN KEY("user_id") REFERENCES "user"("id")
			ON UPDATE CASCADE ON DELETE CASCADE
)