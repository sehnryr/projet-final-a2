import os
import random
from datetime import datetime
from unidecode import unidecode
from faker import Faker

locale = "fr_FR"
country_code = "FR"
fake = Faker(locale)

subdirectory = "datasets"

num_sports = 6
num_cities = 0  # will be calculated when running `generate_cities_dataset()`
num_users = 10000
num_matches = 1000


def write_commands(table_name, columns, path):
    global data_file

    columns = list(map(lambda x: f'"{x}"', columns))

    data_file.write(f"-- Reset and populate table {table_name}\n")
    data_file.write(f'TRUNCATE TABLE "{table_name}" CASCADE;\n')
    data_file.write(f"ALTER SEQUENCE {table_name}_id_seq RESTART;\n")
    data_file.write(f'\copy "{table_name}"({",".join(columns)}) ')
    data_file.write(f"FROM '{path}' DELIMITER ',' csv\n\n")


# Table sport
def generate_sports_dataset():
    table_name = "sport"
    columns = ["name_id", "default_max_players", "default_min_players"]

    with open(file=f"./{subdirectory}/sports.csv", mode="w+") as f:
        f.write("football,22,10\n")
        f.write("basketball,20,14\n")
        f.write("ping_pong,20,10\n")
        f.write("badminton,20,10\n")
        f.write("volleyball,16,12\n")
        f.write("rugby,30,20\n")

        write_commands(table_name, columns, os.path.realpath(f.name))


# Table city
def generate_cities_dataset():
    global num_cities
    table_name = "city"
    columns = [
        "name",
        "postal_code",
        "department_name",
        "department_code",
        "region_name",
        "region_code",
        "latitude",
        "longitude",
    ]

    with open(file=f"./{subdirectory}/cities.csv", mode="w+") as f:
        with open(file="communes-departement-region.csv", mode="r") as fs:
            fs.readline()  # skip first line
            while line := fs.readline().strip():
                city_infos = line.split(",")
                name = city_infos[1]
                postal_code = city_infos[2]
                department_name = city_infos[12]
                department_code = city_infos[11]
                region_name = city_infos[14]
                region_code = city_infos[13]
                latitude = city_infos[5]
                longitude = city_infos[6]

                if (
                    name
                    and postal_code
                    and department_name
                    and department_code
                    and region_name
                    and region_code
                    and latitude
                    and longitude
                ):
                    f.write(
                        ",".join(
                            [
                                name,
                                postal_code,
                                department_name,
                                department_code,
                                region_name,
                                region_code,
                                latitude,
                                longitude,
                            ]
                        )
                        + "\n",
                    )
                    num_cities += 1

        write_commands(table_name, columns, os.path.realpath(f.name))


# Table user
def generate_users_dataset():
    global num_users
    table_name = "user"
    columns = [
        "city_id",
        "first_name",
        "last_name",
        "email",
        "phone_number",
        "password_hash",
        "birthdate",
    ]

    with open(file=f"./{subdirectory}/users.csv", mode="w+") as f:
        for i in range(num_users):
            # if (i % 10000) == 0:
            #     print(i)

            city_id = random.randint(1, num_cities)
            first_name = fake.first_name()
            last_name = fake.last_name()
            phone_number = fake.phone_number()
            password_hash = hash(first_name + last_name + phone_number)
            email = unidecode(
                first_name.lower().replace(" ", "")
                + last_name.lower().replace(" ", "")
                + str(random.randint(0, city_id))
                + str(password_hash)[-3:]
                + "@"
                + fake.safe_domain_name()
            )
            birthdate = fake.date_between(
                start_date="-80y", end_date="-10y"
            ).isoformat()

            f.write(
                ",".join(
                    [
                        str(city_id),
                        first_name,
                        last_name,
                        email,
                        phone_number,
                        str(password_hash),
                        birthdate,
                    ]
                )
                + "\n"
            )

        write_commands(table_name, columns, os.path.realpath(f.name))


# Table user_level
def generate_user_levels_dataset():
    table_name = "user_level"
    columns = ["user_id", "sport_id", "level", "description"]

    with open(file=f"./{subdirectory}/user_levels.csv", mode="w+") as f:
        for user_id in range(1, num_users + 1):
            for sport_id in range(1, random.randint(1, num_sports) + 1):
                level = random.randint(0, 5)
                description = fake.paragraph(nb_sentences=3)
                f.write(
                    ",".join([str(user_id), str(sport_id), str(level), description])
                    + "\n"
                )

        write_commands(table_name, columns, os.path.realpath(f.name))


# Table match
def generate_matches_dataset():
    table_name = "match"
    columns = [
        "organizer_id",
        "sport_id",
        "latitude",
        "longitude",
        "max_players",
        "min_players",
        "price",
        "duration",
        "datetime",
        "description",
        "recommanded_level",
    ]

    with open(file=f"./{subdirectory}/matches.csv", mode="w+") as f:
        for _ in range(num_matches):
            organizer_id = random.randint(1, num_users)
            sport_id = random.randint(1, num_sports)
            latlng = fake.local_latlng(country_code=country_code, coords_only=True)
            latitude = latlng[0]
            longitude = latlng[1]
            min_players = random.randint(1, 10) * 2
            max_players = min_players * 2
            price = random.randint(0, 1000) / 100
            duration = "{0:0>2}:{1:0>2}".format(
                random.randint(2, 5), random.randint(0, 5) * 10
            )
            datetime = fake.date_time_between(
                start_date="now", end_date="+1y"
            ).isoformat()
            description = fake.paragraph(nb_sentences=3)
            recommanded_level = random.randint(0, 5)

            f.write(
                ",".join(
                    [
                        str(organizer_id),
                        str(sport_id),
                        str(latitude),
                        str(longitude),
                        str(max_players),
                        str(min_players),
                        str(price),
                        duration,
                        datetime,
                        description,
                        str(recommanded_level),
                    ]
                )
                + "\n"
            )

        write_commands(table_name, columns, os.path.realpath(f.name))


# Table participation
def generate_participations_dataset():
    table_name = "participation"
    columns = ["user_id", "match_id", "validation", "score"]

    matches = open(file=f"./{subdirectory}/matches.csv", mode="r").readlines()

    with open(file=f"./{subdirectory}/participations.csv", mode="w+") as f:
        for match_id in range(1, num_matches + 1):
            max_players = int(matches[match_id - 1].split(",")[4])
            for user_id in [
                random.randint(1, num_users)
                for _ in range(random.randint(0, max_players))
            ]:
                validation = random.randint(0, 10) > 6
                score = 0
                if validation:
                    score = random.randint(0, 5)

                f.write(
                    ",".join([str(user_id), str(match_id), str(validation), str(score)])
                    + "\n"
                )

        write_commands(table_name, columns, os.path.realpath(f.name))


# Table note
def generate_notes_dataset():
    table_name = "note"
    columns = ["score", "comment", "user_id"]

    with open(file=f"./{subdirectory}/notes.csv", mode="w+") as f:
        for user_id in range(1, num_users + 1):
            comment = fake.paragraph(nb_sentences=5)
            score = random.randint(2, 5)

            f.write(",".join([str(score), comment, str(user_id)]) + "\n")

        write_commands(table_name, columns, os.path.realpath(f.name))


if __name__ == "__main__":
    data_file = open(file="data.sql", mode="w+")

    data_file.write(
        f"""
/*******************************************************************************
Create Date:    {datetime.now().strftime('%Y-%m-%d')}
Description:    Populates the tables of the database.
                Automatically generated by `{os.path.basename(__file__)}` python script.
Usage:          psql -U postgres -d matchmaking -a -f data.sql
                https://stackoverflow.com/a/23992045/12619942
*******************************************************************************/
    """.strip()
        + "\n\n"
    )

    generate_sports_dataset()
    generate_cities_dataset()
    generate_users_dataset()
    generate_user_levels_dataset()
    generate_matches_dataset()
    generate_participations_dataset()
    generate_notes_dataset()

    data_file.close()
