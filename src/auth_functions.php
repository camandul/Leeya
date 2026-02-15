<?php

require_once 'database.php';

/* POSTGRESQL */
// Función para registrar un nuevo usuario
function signUp($name, $email, $password, $location)
{
    try {
        $pdo = getDBConnection();

        // Verificar si el email ya existe (FORMA CORRECTA EN POSTGRES)
        $stmt = $pdo->prepare(
            'SELECT "id" FROM "user" WHERE "email" = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Este email ya está registrado'
            ];
        }

        // Encriptar la contraseña
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insertar el nuevo usuario
        $stmt = $pdo->prepare(
            'INSERT INTO "user" ("name", "email", "passwd", "location")
             VALUES (:name, :email, :passwd, :location)'
        );

        $result = $stmt->execute([
            'name' => $name,
            'email' => $email,
            'passwd' => $hashedPassword,
            'location' => $location
        ]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Usuario registrado exitosamente, ahora puedes iniciar sesión'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al registrar el usuario'
        ];

    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error de base de datos: ' . $e->getMessage()
        ];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Inicio de sesión de usuario
function loginUser($email, $password)
{
    try {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare(
            'SELECT 
                "id",
                "name",
                "email",
                "passwd",
                "signdate",
                "location",
                "lildescription",
                "userrole"
             FROM "user"
             WHERE "email" = :email
             LIMIT 1'
        );

        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['passwd'])) {

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_signdate'] = $user['signdate'];
            $_SESSION['user_location'] = $user['location'];
            $_SESSION['user_description'] = $user['lildescription'];
            $_SESSION['user_role'] = $user['userrole'];

            return [
                'success' => true,
                'message' => 'Inicio de sesión exitoso'
            ];
        }

        return [
            'success' => false,
            'message' => 'Credenciales incorrectas.'
        ];

    } catch (PDOException $e) {
        error_log('PostgreSQL loginUser error: ' . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Error interno al intentar iniciar sesión.'
        ];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Función para verificar si el usuario esta logueado
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}
/* POSTGRESQL */


/* POSTGRESQL */
// Función para verificar si el usuario es administrador
function isAdmin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
/* POSTGRESQL */


/* POSTGRESQL */
// Función para validar la sesión del usuario y eliminar si no existe la cuenta por X razón
function refreshSessionUser()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        return;
    }

    $pdo = getDBConnection();

    $stmt = $pdo->prepare(
        'SELECT
            "id",
            "name",
            "email",
            "signdate",
            "location",
            "lildescription",
            "userrole"
         FROM "user"
         WHERE "id" = :id
         LIMIT 1'
    );

    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user'] = $user;
    } else {
        session_unset();
        session_destroy();
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
function userExists($email)
{
    $pdo = getDBConnection();

    $stmt = $pdo->prepare(
        'SELECT "id" FROM "user" WHERE "email" = :email LIMIT 1'
    );
    $stmt->execute(['email' => $email]);

    return $stmt->fetch() !== false;
}
/* POSTGRESQL */


/* POSTGRESQL */
// Función para cerrar sesion
function logoutUser()
{
    session_unset();
    session_destroy();
}
/* POSTGRESQL */


/* POSTGRESQL */
function changeUserPassword($user_id, $new_password)
{
    try {
        $pdo = getDBConnection();

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'UPDATE "user"
             SET "passwd" = :passwd
             WHERE "id" = :id'
        );

        $result = $stmt->execute([
            'passwd' => $hashed,
            'id' => $user_id
        ]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Contraseña actualizada correctamente.'
            ];
        }

        return [
            'success' => false,
            'message' => 'No se pudo actualizar la contraseña.'
        ];

    } catch (PDOException $e) {
        error_log('PostgreSQL changeUserPassword error: ' . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Error de base de datos al cambiar contraseña.'
        ];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
function changeUserDescription($user_id, $new_description)
{
    try {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare(
            'UPDATE "user"
             SET "lildescription" = :description
             WHERE "id" = :id'
        );

        $result = $stmt->execute([
            'description' => $new_description,
            'id' => $user_id
        ]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Descripción actualizada correctamente.'
            ];
        }

        return [
            'success' => false,
            'message' => 'No se pudo actualizar la descripción.'
        ];

    } catch (PDOException $e) {
        error_log('PostgreSQL changeUserDescription error: ' . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Error de base de datos al cambiar la descripción.'
        ];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
function changeUserLocation($user_id, $new_location)
{
    try {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare(
            'UPDATE "user"
             SET "location" = :location
             WHERE "id" = :id'
        );

        $result = $stmt->execute([
            'location' => $new_location,
            'id' => $user_id
        ]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Ubicación actualizada correctamente.'
            ];
        }

        return [
            'success' => false,
            'message' => 'No se pudo actualizar la ubicación.'
        ];

    } catch (PDOException $e) {
        error_log('PostgreSQL changeUserLocation error: ' . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Error de base de datos al cambiar la ubicación.'
        ];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Crear un libro (publicar)
function createBook(
    $ownerid,
    $name,
    $author,
    $genre,
    $editorial,
    $description,
    $qstatus,
    $bookpic,
    $typeof,
    $status,
    $price = null,
    $limdate = null
) {
    try {
        $pdo = getDBConnection();

        $fields = [
            '"ownerid"',
            '"name"',
            '"author"',
            '"genre"',
            '"editorial"',
            '"description"',
            '"qstatus"',
            '"bookpic"',
            '"typeof"',
            '"status"'
        ];

        $placeholders = [
            ':ownerid',
            ':name',
            ':author',
            ':genre',
            ':editorial',
            ':description',
            ':qstatus',
            ':bookpic',
            ':typeof',
            ':status'
        ];

        $params = [
            'ownerid' => $ownerid,
            'name' => $name,
            'author' => $author,
            'genre' => $genre,
            'editorial' => $editorial,
            'description' => $description,
            'qstatus' => $qstatus,
            'bookpic' => $bookpic,
            'typeof' => $typeof,
            'status' => $status
        ];

        if ($price !== null) {
            $fields[] = '"price"';
            $placeholders[] = ':price';
            $params['price'] = $price;
        }

        if ($typeof === 'Subasta' && $limdate !== null) {
            $fields[] = '"limdate"';
            $placeholders[] = ':limdate';
            $params['limdate'] = $limdate;
        }

        $sql = sprintf(
            'INSERT INTO "book" (%s) VALUES (%s)',
            implode(', ', $fields),
            implode(', ', $placeholders)
        );

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Libro publicado exitosamente.'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al publicar el libro.'
        ];

    } catch (PDOException $e) {
        error_log('PostgreSQL createBook error: ' . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Error de base de datos al publicar el libro.'
        ];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Obtener los libros del usuario x su id
function getBooksByUserId($user_id)
{
    try {
        $pdo = getDBConnection();

        $sql = '
            SELECT
                "id",
                "name",
                "author",
                "genre",
                "editorial",
                "description",
                "qstatus",
                "bookpic",
                "typeof",
                "status",
                "price"
            FROM "book"
            WHERE "ownerid" = :user_id
              AND "status" = true
            ORDER BY "id" DESC
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('PostgreSQL getBooksByUserId error: ' . $e->getMessage());
        return [];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Obtener datos de usuarios por id
function getUserById($user_id)
{
    try {
        $pdo = getDBConnection();

        $sql = '
            SELECT
                "id",
                "name",
                "email",
                "location",
                "lildescription",
                "signdate",
                "userrole"
            FROM "user"
            WHERE "id" = :id
            LIMIT 1
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $user_id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('PostgreSQL getUserById error: ' . $e->getMessage());
        return null;
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Obtener los 4 libros + recientes xra el index
function getLatestBooks($limit = 4, $exclude_user_id = null)
{
    try {
        $pdo = getDBConnection();

        $sql = '
            SELECT b.*
            FROM "book" b
            JOIN "user" u ON u.id = b.ownerid
            WHERE b."status" IS TRUE
              AND u."userrole" != \'banned\'
        ';

        $params = [];

        if ($exclude_user_id !== null) {
            $sql .= ' AND b."ownerid" != :exclude_user_id';
            $params['exclude_user_id'] = $exclude_user_id;
        }

        $sql .= ' ORDER BY b."id" DESC LIMIT ' . (int) $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('PostgreSQL getLatestBooks error: ' . $e->getMessage());
        return [];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Obtener los libros para explore
function searchBooks($search = '', $type = '', $exclude_user_id = null, $current_user_role = 'user')
{
    try {
        $pdo = getDBConnection();

        $sql = '
            SELECT
                b.*,
                u."name" AS owner_name
            FROM "book" b
            JOIN "user" u ON u."id" = b."ownerid"
            WHERE b."status" IS TRUE
        ';

        // Solo filtrar usuarios banneados si el usuario actual NO es admin
        if ($current_user_role !== 'admin') {
            $sql .= ' AND u."userrole" != \'banned\'';
        }

        $params = [];

        if ($exclude_user_id !== null) {
            $sql .= ' AND b."ownerid" != :exclude_user_id';
            $params['exclude_user_id'] = $exclude_user_id;
        }

        if ($type !== '') {
            $sql .= ' AND b."typeof" = :type';
            $params['type'] = $type;
        }

        if ($search !== '') {
            $sql .= '
                AND (
                    b."name" ILIKE :search
                    OR b."author" ILIKE :search
                    OR b."genre" ILIKE :search
                )
            ';
            $params['search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY b."id" DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('PostgreSQL searchBooks error: ' . $e->getMessage());
        return [];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Buscar usuarios por nombre
function searchUsers($search = '', $exclude_user_id = null)
{
    try {
        $pdo = getDBConnection();

        $sql = '
            SELECT
                "id",
                "name",
                "email",
                "location",
                "lildescription"
            FROM "user"
            WHERE 1 = 1
        ';

        $params = [];

        if ($exclude_user_id !== null) {
            $sql .= ' AND "id" != :exclude_user_id';
            $params['exclude_user_id'] = $exclude_user_id;
        }

        if ($search !== '') {
            $sql .= ' AND "name" ILIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY "name" ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('PostgreSQL searchUsers error: ' . $e->getMessage());
        return [];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Obtener usuarios de tipo user y banned para admin
function getAllUsersForAdmin()
{
    try {
        $pdo = getDBConnection();

        $sql = '
            SELECT
                "id",
                "name",
                "email",
                "location",
                "lildescription",
                "signdate",
                "userrole"
            FROM "user"
            WHERE "userrole" IN (\'user\', \'banned\')
            ORDER BY
                "userrole" DESC,
                "id" DESC
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('PostgreSQL getAllUsersForAdmin error: ' . $e->getMessage());
        return [];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Obtener todos los libros (PostgreSQL)
function getAllBooks()
{
    try {
        $pdo = getDBConnection();

        $sql = '
            SELECT
                b.*,
                u."name"     AS owner_name,
                u."userrole" AS owner_role
            FROM "book" b
            INNER JOIN "user" u
                ON b."ownerid" = u."id"
            ORDER BY b."id" DESC
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('PostgreSQL getAllBooks error: ' . $e->getMessage());
        return [];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Crear propuestas
function createProposal($interested, $targetbookid, $type, $money = null)
{
    try {
        $pdo = getDBConnection();

        $sql = '
            INSERT INTO "proposal" 
                ("interested", "targetbookid", "money", "status", "proposaldate") 
            VALUES 
                (:interested, :targetbookid, :money, \'En proceso\', CURRENT_DATE)
            RETURNING "id"
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'interested' => $interested,
            'targetbookid' => $targetbookid,
            'money' => $money
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['id'];

    } catch (PDOException $e) {
        error_log('PostgreSQL createProposal error: ' . $e->getMessage());
        return false;
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Propuestas hechas
function getSentProposals($user_id)
{
    try {
        $pdo = getDBConnection();
        $week_ago = date('Y-m-d', strtotime('-7 days'));

        $sql = '
            SELECT 
                p.*, 
                b."name" AS book_name, 
                b."author", 
                b."bookpic", 
                b."id" AS book_id,
                b."typeof", 
                b."price", 
                b."ownerid", 
                u."name" AS owner_name,
                u."id" AS owner_id,
                u."email" AS owner_email
            FROM "proposal" p
            JOIN "book" b ON p."targetbookid" = b."id"
            JOIN "user" u ON b."ownerid" = u."id"
            WHERE 
                p."interested" = :user_id
                AND p."proposaldate" >= :week_ago
                AND u."userrole" != \'banned\'
                AND (
                    b."status" IS TRUE
                    OR (b."status" IS FALSE AND p."status" = \'Finalizada\')
                    OR (b."status" IS FALSE AND b."typeof" = \'Subasta\')
                )
                AND (p."status" = \'En proceso\' OR p."status" = \'Finalizada\' OR p."status" = \'Rechazada\')
            ORDER BY p."id" DESC
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'week_ago' => $week_ago
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('PostgreSQL getSentProposals error: ' . $e->getMessage());
        return [];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Propuestas recibidas
function getReceivedProposals($user_id)
{
    try {
        $pdo = getDBConnection();
        $week_ago = date('Y-m-d', strtotime('-7 days'));

        $sql = '
            SELECT 
                p.*, 
                b."name" AS book_name, 
                b."author", 
                b."id" AS book_id,
                b."bookpic", 
                b."typeof", 
                b."price", 
                u."name" AS interested_name, 
                u."id" AS interested_id,
                u."email" AS interested_email
            FROM "proposal" p
            JOIN "book" b ON p."targetbookid" = b."id"
            JOIN "user" u ON p."interested" = u."id"
            WHERE 
                b."ownerid" = :user_id
                AND p."proposaldate" >= :week_ago
                AND u."userrole" != \'banned\'
                AND (
                    b."status" IS TRUE
                    OR (b."status" IS FALSE AND p."status" = \'Finalizada\')
                )
                AND (p."status" = \'En proceso\' OR p."status" = \'Finalizada\' OR p."status" = \'Rechazada\')
            ORDER BY p."id" DESC
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'week_ago' => $week_ago
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('PostgreSQL getReceivedProposals error: ' . $e->getMessage());
        return [];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Obtener el número de propuestas para mostrar en notificaciones
function getPendingProposalsCount($user_id)
{
    try {
        $pdo = getDBConnection();

        // Enviadas
        $sql1 = '
            SELECT COUNT(*) 
            FROM "proposal" p
            JOIN "book" b ON p."targetbookid" = b."id"
            JOIN "user" u ON b."ownerid" = u."id"
            WHERE 
                p."interested" = :user_id 
                AND p."status" = \'En proceso\'
                AND b."status" IS TRUE
                AND u."userrole" != \'banned\'
        ';

        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute(['user_id' => $user_id]);
        $sent = $stmt1->fetchColumn();

        // Recibidas
        $sql2 = '
            SELECT COUNT(*) 
            FROM "proposal" p
            JOIN "book" b ON p."targetbookid" = b."id"
            JOIN "user" u ON p."interested" = u."id"
            WHERE 
                b."ownerid" = :user_id 
                AND b."status" IS TRUE
                AND (p."status" = \'En proceso\' OR p."status" = \'Finalizada\')
                AND u."userrole" != \'banned\'
        ';

        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute(['user_id' => $user_id]);
        $received = $stmt2->fetchColumn();

        return [
            'sent' => (int) $sent,
            'received' => (int) $received
        ];

    } catch (PDOException $e) {
        error_log('PostgreSQL getPendingProposalsCount error: ' . $e->getMessage());
        return ['sent' => 0, 'received' => 0];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Obtiene el libro segun el id del mismo
function getBookById($book_id)
{
    try {
        $pdo = getDBConnection();

        $sql = 'SELECT * FROM "book" WHERE "id" = :book_id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'book_id' => $book_id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('PostgreSQL getBookById error: ' . $e->getMessage());
        return null;
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Obtener reseñas de usuario segun id
function getUserRates($user_id)
{
    try {
        $pdo = getDBConnection();

        $sql = '
            SELECT r.*, u."name" AS sender_name
            FROM "rate" r
            JOIN "user" u ON r."rater" = u."id"
            WHERE r."ratee" = :user_id
            ORDER BY r."id" DESC
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('PostgreSQL getUserRates error: ' . $e->getMessage());
        return [];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Registrar una reseña
function createUserRate($sender_id, $target_id, $stars, $description)
{
    try {
        $pdo = getDBConnection();

        $sql = '
            INSERT INTO "rate" 
                ("rater", "ratee", "rating", "commentary", "ratedate") 
            VALUES 
                (:sender_id, :target_id, :stars, :description, NOW())
        ';

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            'sender_id' => $sender_id,
            'target_id' => $target_id,
            'stars' => $stars,
            'description' => $description
        ]);

    } catch (PDOException $e) {
        error_log('PostgreSQL createUserRate error: ' . $e->getMessage());
        return false;
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Eliminar libro por admin (con validación de rol)
function deleteBookByAdmin($book_id, $admin_user_id)
{
    try {
        $pdo = getDBConnection();

        // Validar que el usuario sea admin
        $sql1 = 'SELECT "userrole" FROM "user" WHERE "id" = :admin_user_id';

        $stmt = $pdo->prepare($sql1);
        $stmt->execute(['admin_user_id' => $admin_user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['userrole'] !== 'admin') {
            error_log('Intento no autorizado de eliminar libro por usuario ID: ' . $admin_user_id);
            return false;
        }

        // Proceder a eliminar el libro (cambiar status a FALSE)
        $sql2 = 'UPDATE "book" SET "status" = FALSE WHERE "id" = :book_id';

        $stmt = $pdo->prepare($sql2);
        $result = $stmt->execute(['book_id' => $book_id]);

        if ($result) {
            // Registrar la acción en log
            error_log('Admin ID ' . $admin_user_id . ' eliminó el libro ID ' . $book_id);
        }

        return $result;

    } catch (PDOException $e) {
        error_log('PostgreSQL deleteBookByAdmin error: ' . $e->getMessage());
        return false;
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Registrar un reporte
function createUserReport($sender_id, $target_id, $motive, $description)
{
    try {
        $pdo = getDBConnection();

        $sql = '
            INSERT INTO "reports" 
                ("idreporter", "idreported", "motive", "description", "datereport", "ischecked") 
            VALUES 
                (:sender_id, :target_id, :motive, :description, NOW(), FALSE)
        ';

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            'sender_id' => $sender_id,
            'target_id' => $target_id,
            'motive' => $motive,
            'description' => $description
        ]);

    } catch (PDOException $e) {
        error_log('PostgreSQL createUserReport error: ' . $e->getMessage());
        return false;
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Obtener todos los reportes no chequeados
function getUncheckedReports()
{
    try {
        $pdo = getDBConnection();

        $sql = '
            SELECT 
                r."id",
                r."motive",
                r."description",
                r."datereport",
                ur."id" AS reporter_id,
                ur."name" AS reporter_name,
                uu."id" AS reported_id,
                uu."name" AS reported_name
            FROM "reports" r
            INNER JOIN "user" ur ON r."idreporter" = ur."id"
            INNER JOIN "user" uu ON r."idreported" = uu."id"
            WHERE r."ischecked" IS FALSE
            ORDER BY r."datereport" DESC
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('PostgreSQL getUncheckedReports error: ' . $e->getMessage());
        return [];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Marcar reporte como chequeado
function markReportAsChecked($report_id)
{
    try {
        $pdo = getDBConnection();

        $sql = 'UPDATE "reports" SET "ischecked" = TRUE WHERE "id" = :report_id';

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute(['report_id' => $report_id]);

        if ($result) {
            return ['success' => true, 'message' => 'Reporte marcado como revisado.'];
        } else {
            return ['success' => false, 'message' => 'No se pudo marcar el reporte.'];
        }

    } catch (PDOException $e) {
        error_log('PostgreSQL markReportAsChecked error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error de base de datos.'];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Bannear un usuario (cambiar estado a banned)
function banUser($user_id)
{
    try {
        $pdo = getDBConnection();
        
        $sql = 'UPDATE "user" SET "userrole" = \'banned\' WHERE "id" = :user_id';
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute(['user_id' => $user_id]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Usuario baneado correctamente.'];
        } else {
            return ['success' => false, 'message' => 'No se pudo bannear al usuario.'];
        }
        
    } catch (PDOException $e) {
        error_log('PostgreSQL banUser error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error de base de datos al bannear usuario.'];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Desbannear un usuario (cambiar estado de banned a user)
function unbanUser($user_id)
{
    try {
        $pdo = getDBConnection();
        
        $sql = 'UPDATE "user" SET "userrole" = \'user\' WHERE "id" = :user_id';
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute(['user_id' => $user_id]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Usuario desbaneado correctamente.'];
        } else {
            return ['success' => false, 'message' => 'No se pudo desbannear al usuario.'];
        }
        
    } catch (PDOException $e) {
        error_log('PostgreSQL unbanUser error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error de base de datos al desbannear usuario.'];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Edición del libro por usuario dueño
function updateBook($book_id, $data)
{
    try {
        $pdo = getDBConnection();
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = '"' . $key . '" = :' . $key;
            $params[$key] = $value;
        }
        $params['book_id'] = $book_id;

        $sql = 'UPDATE "book" SET ' . implode(', ', $fields) . ' WHERE "id" = :book_id';
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute($params);
        
    } catch (PDOException $e) {
        error_log('PostgreSQL updateBook error: ' . $e->getMessage());
        return false;
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Eliminar (cambiar el estado del libro)
function deleteBook($book_id)
{
    try {
        $pdo = getDBConnection();
        
        $sql = 'UPDATE "book" SET "status" = FALSE WHERE "id" = :book_id';
        
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute(['book_id' => $book_id]);
        
    } catch (PDOException $e) {
        error_log('PostgreSQL deleteBook error: ' . $e->getMessage());
        return false;
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Cambiar estado de propuesta
function updateProposalStatus($proposal_id, $new_status)
{
    try {
        $pdo = getDBConnection();
        
        $sql = 'UPDATE "proposal" SET "status" = :new_status WHERE "id" = :proposal_id';
        
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([
            'new_status' => $new_status,
            'proposal_id' => $proposal_id
        ]);
        
    } catch (PDOException $e) {
        error_log('PostgreSQL updateProposalStatus error: ' . $e->getMessage());
        return false;
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Finalizar propuesta y deshabilitar libro
function finalizeProposal($proposal_id)
{
    try {
        $pdo = getDBConnection();
        
        // Cambia estado de propuesta
        $sql1 = 'UPDATE "proposal" SET "status" = \'Finalizada\' WHERE "id" = :proposal_id';
        $stmt = $pdo->prepare($sql1);
        $stmt->execute(['proposal_id' => $proposal_id]);
        
        // Marca libro como no disponible
        $sql2 = 'UPDATE "book" SET "status" = FALSE WHERE "id" = (SELECT "targetbookid" FROM "proposal" WHERE "id" = :proposal_id)';
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute(['proposal_id' => $proposal_id]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log('PostgreSQL finalizeProposal error: ' . $e->getMessage());
        return false;
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Funcion dedicada a cambiar el estado de libros de subasta con fecha excedida
function updateExpiredAuctions()
{
    try {
        $pdo = getDBConnection();
        $today = date('Y-m-d');
        
        $sql = '
            UPDATE "book"
            SET "status" = FALSE
            WHERE "typeof" = \'Subasta\'
              AND "limdate" IS NOT NULL
              AND "limdate" < :today
              AND "status" IS TRUE
        ';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['today' => $today]);
        
    } catch (PDOException $e) {
        error_log('PostgreSQL updateExpiredAuctions error: ' . $e->getMessage());
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Crear propuesta de intercambio (varios libros ofrecidos)
function createExchangeProposal($interested, $targetbookid, $offered_books)
{
    $proposal_id = createProposal($interested, $targetbookid, 'Intercambio');
    if ($proposal_id && is_array($offered_books)) {
        try {
            $pdo = getDBConnection();
            
            $sql = 'INSERT INTO "proposal_book" ("bookid", "proposalid") VALUES (:bookid, :proposalid)';
            $stmt = $pdo->prepare($sql);
            
            foreach ($offered_books as $offered_book_id) {
                $stmt->execute([
                    'bookid' => $offered_book_id,
                    'proposalid' => $proposal_id
                ]);
            }
            
            return $proposal_id;
            
        } catch (PDOException $e) {
            error_log('PostgreSQL createExchangeProposal error: ' . $e->getMessage());
            return false;
        }
    }
    return false;
}
/* POSTGRESQL */


/* POSTGRESQL */
// Crear propuesta de subasta y actualizar monto en book
function createAuctionProposal($interested, $targetbookid, $amount)
{
    $proposal_id = createProposal($interested, $targetbookid, 'Subasta', $amount);
    if ($proposal_id) {
        try {
            $pdo = getDBConnection();
            
            $sql = 'UPDATE "book" SET "price" = :amount WHERE "id" = :targetbookid';
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'amount' => $amount,
                'targetbookid' => $targetbookid
            ]);
            
            return $proposal_id;
            
        } catch (PDOException $e) {
            error_log('PostgreSQL createAuctionProposal error: ' . $e->getMessage());
            return false;
        }
    }
    return false;
}
/* POSTGRESQL */


/* POSTGRESQL */
// Cancelar automáticamente propuestas antiguas (más de 7 días sin actualizar)
function cancelOldProposals()
{
    try {
        $pdo = getDBConnection();
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        
        $sql = '
            UPDATE "proposal" 
            SET "status" = \'Cancelada\' 
            WHERE "status" = \'En proceso\' 
            AND "proposaldate" < :week_ago
        ';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['week_ago' => $week_ago]);
        
    } catch (PDOException $e) {
        error_log('PostgreSQL cancelOldProposals error: ' . $e->getMessage());
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Obtener libros ofrecidos en una propuesta de intercambio
function getExchangeBooks($proposal_id)
{
    try {
        $pdo = getDBConnection();
        
        $sql = '
            SELECT b."id", b."name", b."author", b."bookpic"
            FROM "proposal_book" pb
            JOIN "book" b ON pb."bookid" = b."id"
            WHERE pb."proposalid" = :proposal_id
        ';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['proposal_id' => $proposal_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log('PostgreSQL getExchangeBooks error: ' . $e->getMessage());
        return [];
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Verificar si UN USUARIO ESPECÍFICO ya ha reseñado una propuesta
function existsRatingForProposal($proposal_id, $rater_id)
{
    try {
        $pdo = getDBConnection();
        
        // Intentar verificar en tabla rate con proposal_id (nueva estructura)
        $sql = '
            SELECT "id"
            FROM "rate"
            WHERE "proposal_id" = :proposal_id
            AND "rater" = :rater_id
            LIMIT 1
        ';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'proposal_id' => $proposal_id,
            'rater_id' => $rater_id
        ]);
        
        $result = $stmt->fetch();
        return $result !== false;
        
    } catch (PDOException $e) {
        // Si la columna proposal_id no existe, usar fallback
        error_log('PostgreSQL existsRatingForProposal error: ' . $e->getMessage());
        return false;
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Guardar una reseña en la base de datos - permite que ambos usuarios reseñen
function rateUser($rater_id, $ratee_id, $rating, $commentary, $proposal_id)
{
    try {
        $pdo = getDBConnection();
        
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Verificar si ESTE usuario ya reseñó esta propuesta
        $checkSql = '
            SELECT "id"
            FROM "rate"
            WHERE "proposal_id" = :proposal_id
            AND "rater" = :rater_id
            LIMIT 1
        ';
        
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            'proposal_id' => $proposal_id,
            'rater_id' => $rater_id
        ]);
        
        // Si este usuario ya reseñó, rechazar
        if ($checkStmt->fetch() !== false) {
            $pdo->rollBack();
            return false;
        }
        
        // Guardar la reseña con proposal_id
        $insertSql = '
            INSERT INTO "rate" ("rater", "ratee", "rating", "commentary", "proposal_id", "ratedate")
            VALUES (:rater_id, :ratee_id, :rating, :commentary, :proposal_id, CURRENT_DATE)
        ';
        
        $insertStmt = $pdo->prepare($insertSql);
        $insertResult = $insertStmt->execute([
            'rater_id' => $rater_id,
            'ratee_id' => $ratee_id,
            'rating' => intval($rating),
            'commentary' => $commentary,
            'proposal_id' => $proposal_id
        ]);
        
        if (!$insertResult) {
            $pdo->rollBack();
            return false;
        }
        
        // Confirmar transacción
        $pdo->commit();
        return true;
        
    } catch (PDOException $e) {
        error_log('PostgreSQL rateUser error: ' . $e->getMessage());
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Exception $rollbackError) {
            error_log('Rollback error: ' . $rollbackError->getMessage());
        }
        return false;
    }
}
/* POSTGRESQL */


/* POSTGRESQL */
// Obtener reseña de un usuario a otro si existe
function getUserRating($rater_id, $ratee_id)
{
    try {
        $pdo = getDBConnection();
        
        $sql = '
            SELECT "rating", "commentary", "ratedate"
            FROM "rate"
            WHERE "rater" = :rater_id 
            AND "ratee" = :ratee_id
            ORDER BY "ratedate" DESC
            LIMIT 1
        ';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'rater_id' => $rater_id,
            'ratee_id' => $ratee_id
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log('PostgreSQL getUserRating error: ' . $e->getMessage());
        return null;
    }
}
/* POSTGRESQL */