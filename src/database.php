<?php
function getDBConnection()
{
    try {
        $host = getenv('DB_HOST');
        $port = getenv('DB_PORT') ?: 5432;
        $dbname = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

        $pdo = new PDO(
            $dsn,
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return $pdo;

    } catch (PDOException $e) {
        die("ERROR POSTGRES: " . $e->getMessage());
    }
}
/*

CREATE TABLE "user" (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    passwd VARCHAR(255) NOT NULL,
    signdate DATE DEFAULT CURRENT_DATE,
    location VARCHAR(255) NOT NULL,
    lildescription VARCHAR(255) DEFAULT '',
    userrole VARCHAR(100) DEFAULT 'user'
);

CREATE TABLE book (
    id SERIAL PRIMARY KEY,
    ownerid INT REFERENCES "user"(id) ON DELETE SET NULL,
    name VARCHAR(255),
    author VARCHAR(255),
    genre VARCHAR(100),
    editorial VARCHAR(255),
    description TEXT,
    qstatus NUMERIC,
    bookpic VARCHAR(500),
    typeof VARCHAR(50),
    status BOOLEAN,
    price NUMERIC(10,2),
    limdate DATE
);

CREATE TABLE proposal (
    id SERIAL PRIMARY KEY,
    interested INT REFERENCES "user"(id) ON DELETE CASCADE,
    targetbookid INT REFERENCES book(id) ON DELETE CASCADE,
    money NUMERIC(10,2),
    status VARCHAR(50),
    proposaldate DATE DEFAULT CURRENT_DATE
);

CREATE TABLE proposal_book (
    id SERIAL PRIMARY KEY,
    bookid INT REFERENCES book(id) ON DELETE CASCADE,
    proposalid INT REFERENCES proposal(id) ON DELETE CASCADE
);

CREATE TABLE rate (
    id SERIAL PRIMARY KEY,
    rater INT REFERENCES "user"(id) ON DELETE CASCADE,
    ratee INT REFERENCES "user"(id) ON DELETE CASCADE,
    rating NUMERIC,
    commentary VARCHAR(500),
    ratedate DATE DEFAULT CURRENT_DATE
);

CREATE TABLE reports (
    id SERIAL PRIMARY KEY,
    idreporter INT REFERENCES "user"(id) ON DELETE CASCADE,
    idreported INT REFERENCES "user"(id) ON DELETE CASCADE,
    motive VARCHAR(255),
    description TEXT,
    datereport DATE DEFAULT CURRENT_DATE,
    ischecked BOOLEAN DEFAULT FALSE
);


INSERT INTO user (
    name,
    email,
    passwd,
    signdate,
    location,
    userrole
) VALUES (
    'Admin',
    'admin@admin.com',
    '$2y$10$NOeiZ/8J45lVLSe2P/jb6.ZyRIoiSksFk2qBr03a29C/T3612KzAy', -- Contraseña temporal "123456"
    CURDATE(),
    'Sistema',
    'admin'
);

*/
?>