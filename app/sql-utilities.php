<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/utilities.php';
loadDotEnv();

use MyApp\EditableException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DB
{
    // { <nom_requete> : { sql: string, args: array<string> } }
    function getQuery($key)
    {
        return [
            // SELECT ALL
            'select_all_users' => 'select * from users',
            'select_all_app_config' => 'select * from app_config',
            // SELECT ONE
            'select_user_from_id_user' => 'select * from users where id_user = :id_user',
            'select_user_from_email' => 'select * from users where email = :email',
            'select_user_from_username' => 'select * from users where username = :username',
            'select_app_config_from_config_key' => 'select config_value from app_config where config_key = :config_key',
            // UPDATE
            'update_password_hash' => 'update users set password_hash = :new_password_hash where id_user = :id_user',
            'update_last_user_update' => 'update users set last_user_update = :new_date where id_user = :id_user',
            'update_user_profile' => 'update users set username = :username where id_user = :id_user',
            'update_user_profile_picture' => 'update users set profile_picture = :profile_picture where id_user = :id_user',
            'update_app_config' => 'update app_config set config_key = :config_key, config_value = :config_value where id = :id',
            // Backup Email Management
            'update_pending_backup_email' => 'update users set pending_backup_email = :email, backup_email_verification_token = :token where id_user = :id_user',
            'verify_backup_email' => 'update users set backup_email = pending_backup_email, backup_email_verified_at = datetime("now"), pending_backup_email = null, backup_email_verification_token = null where id_user = :id_user and backup_email_verification_token = :token',
            'remove_backup_email' => 'update users set backup_email = null, backup_email_verified_at = null where id_user = :id_user',
            'clear_pending_backup_email' => 'update users set pending_backup_email = null, backup_email_verification_token = null where id_user = :id_user',
            // INSERT
            'insert_user' => 'insert into users(email, user_role, password_hash, username) values (:email, :user_role, :password_hash, :username)',
            'insert_app_config' => 'insert into app_config (config_key, config_value) values (:config_key, :config_value)',
            // User Emails
            'select_user_email_by_email' => 'SELECT * FROM user_emails WHERE email = :email',
            'insert_user_email' => 'INSERT INTO user_emails (user_id, email, is_verified, is_default, verification_token) VALUES (:user_id, :email, 0, 0, :verification_token)',
            'select_user_email_by_user_id_and_email' => 'SELECT * FROM user_emails WHERE user_id = :user_id AND email = :email',
            'update_user_email_verified' => 'UPDATE user_emails SET is_verified = 1, verification_token = NULL WHERE id = :id',
            'update_user_emails_unset_default' => 'UPDATE user_emails SET is_default = 0 WHERE user_id = :user_id',
            'update_user_email_set_default' => 'UPDATE user_emails SET is_default = 1 WHERE id = :id AND user_id = :user_id',
            'select_user_email_by_id_and_user_id' => 'SELECT * FROM user_emails WHERE id = :id AND user_id = :user_id',
            'select_user_emails_by_user_id' => 'SELECT * FROM user_emails WHERE user_id = :user_id',
            'update_user_email_set_pending_deletion' => 'UPDATE user_emails SET is_pending_deletion = 1, deletion_token = :deletion_token WHERE id = :id AND user_id = :user_id',
            'select_user_email_pending_deletion_by_id_token_and_user_id' => 'SELECT * FROM user_emails WHERE id = :id AND deletion_token = :deletion_token AND user_id = :user_id AND is_pending_deletion = 1',
            'update_user_email_cancel_pending_deletion' => 'UPDATE user_emails SET is_pending_deletion = 0, deletion_token = NULL WHERE id = :id',
            // DELETE
            'delete_app_config' => 'DELETE FROM app_config WHERE id = :id',
            '' => '',
        ][$key];
    }

    private $_db_type;
    private $_db;

    public function __construct()
    {
        $this->_db_type = strtolower($_ENV['db_type']);
        switch ($this->_db_type) {
            case 'sqlite3':
                if (!class_exists('SQLite3'))
                    throw new \Exception("SQLite 3 is NOT supported");
                $this->_db = new SQLite3(__DIR__ . '/../db/' . $_ENV['db_name'] . '.db');
                break;
            case 'mariadb':
                try {
                    $this->_db = new \PDO(
                        'mysql:host=localhost;dbname=' . $_ENV['db_name'] . ';charset=utf8mb4',
                        $_ENV['db_username'],
                        $_ENV['db_password'],
                    );
                    $this->_db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                    // Attention, la ligne suivante ne marche que si les timezone sont installés sur la machine
                    // https://dev.mysql.com/downloads/timezones.html
                    // mais par défaut, il vaut mieux ne rien mettre et simplement laisser MySQL se caller sur la timezone de l'OS
                    // $db->exec('SET SESSION time_zone = 'Europe/Paris'');
                } catch (\Exception $e) {
                    throw $e;
                }
                break;
            default:
                throw new \Exception("DB type unknown");
        }
    }

    public function query($sql_string)
    {
        switch ($this->_db_type) {
            case 'sqlite3':
                return $this->_db->exec($sql_string);
            case 'mariadb':
                return $this->_db->query($sql_string);
            default:
                console_log($this->_db_type);
                throw new \Exception("Unknown DB type");
        }
    }

    public function prepare($sql_string)
    {
        switch ($this->_db_type) {
            case 'sqlite3':
            case 'mariadb':
                return new DBStatement($this->_db->prepare($sql_string));
            default:
                console_log($this->_db_type);
                throw new \Exception("Unknown DB type");
        }
    }

    public function prepareNamedQuery($request_name)
    {
        return $this->prepare($this->getQuery($request_name));
    }

    public function getColumnNames(string $tableName): array
    {
        switch ($this->_db_type) {
            case 'sqlite3':
                /** @var SQLite3Result */
                $result = $this->_db->query("PRAGMA table_info('$tableName')");
                $column_names = [];
                // Iterate over the result set
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $column_names[] = $row['name'];
                }
                return $column_names;
            case 'mariadb':
                $result = $this->_db->query("SHOW COLUMNS FROM `$tableName`");
                $column_names = [];
                while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
                    $column_names[] = $row['Field'];
                }
                return $column_names;
            default:
                throw new \Exception("Unknown DB type");
        }
    }

    public function getColumnsTypes(string $tableName): array
    {
        switch ($this->_db_type) {
            case 'sqlite3':
                /** @var SQLite3Result */
                $result = $this->_db->query("PRAGMA table_info('$tableName')");
                $column_types = [];
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    if (strpos($row['type'], 'INTEGER') !== false) {
                        if ($row['pk'] == 1) {
                            $column_types[] = 'INTEGER PRIMARY KEY';
                        } else {
                            $column_types[] = 'INTEGER';
                        }
                    } elseif (strpos($row['type'], 'TEXT') !== false || strpos($row['type'], 'varchar') !== false) {
                        $column_types[] = 'TEXT';
                    } elseif (strpos($row['type'], 'BOOLEAN') !== false) {
                        $column_types[] = 'BOOLEAN';
                    } elseif (strpos($row['type'], 'DATETIME') !== false) {
                        $column_types[] = 'DATETIME';
                    }
                }
                return $column_types;
            case 'mariadb':
                $result = $this->_db->query("SHOW COLUMNS FROM `$tableName`");
                $column_types = [];
                while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
                    $column_types[] = $row['Type'];
                }
                return $column_types;
            default:
                throw new \Exception("Unknown DB type");
        }
    }
}

class DBStatement
{
    private $_stmt;
    private $_db_type;
    private $_sqlite3_result;

    public function __construct($stmt)
    {
        $this->_stmt = $stmt;
        $this->_db_type = strtolower($_ENV['db_type']);
    }

    public function execute($input_parameters = null)
    {
        switch ($this->_db_type) {
            case 'sqlite3':
                if ($input_parameters != null) {
                    foreach ($input_parameters as $key => $value) {
                        $this->_stmt->bindValue(':' . $key, $value);
                    }
                }
                $this->_sqlite3_result = $this->_stmt->execute();
                return $this;
            case 'mariadb':
                return $this->_stmt->execute($input_parameters);
            default:
                console_log($this->_db_type);
                throw new \Exception("Unknown DB type");
        }
    }

    public function fetch()
    {
        switch ($this->_db_type) {
            case 'sqlite3':
                return $this->_sqlite3_result->fetchArray(SQLITE3_ASSOC);
            case 'mariadb':
                return $this->_stmt->fetch();
            default:
                console_log($this->_db_type);
                throw new \Exception("Unknown DB type");
        }
    }

    public function fetchAll()
    {
        switch ($this->_db_type) {
            case 'sqlite3':
                $res = [];
                while ($row = $this->_sqlite3_result->fetchArray())
                    $res[] = $row;
                return $res;
            case 'mariadb':
                return $this->_stmt->fetchAll();
            default:
                console_log($this->_db_type);
                throw new \Exception("Unknown DB type");
        }
    }

    public function fetchColumn($column = 0)
    {
        $this->fetch()[$column];
    }

    public function rowCount()
    {
        switch ($this->_db_type) {
            case 'sqlite3':
                console_log($this->fetchAll());
                return count($this->fetchAll());
            case 'mariadb':
                return $this->_stmt->rowCount();
            default:
                console_log($this->_db_type);
                throw new \Exception("Unknown DB type");
        }
    }
}

function runMysqlFile($filename)
{
    $connexion_string = "mysql --user=" . $_ENV['db_username'] . " -p" . $_ENV['db_password'] . " " . $_ENV['db_name'] . ' --default-character-set=utf8';
    // echo $connexion_string . "\n";

    echo "--- $filename ---\n";
    $tmpString = file_get_contents(__DIR__ . '/sql/' . $filename);
    $tmpString = str_replace(':db_name', $_ENV['db_name'], $tmpString);

    $temp = tmpfile();
    fwrite($temp, $tmpString);
    $res = exec($connexion_string . ' -e "source ' . stream_get_meta_data($temp)['uri'] . '"');
    echo $res;
    fclose($temp);

    echo "\n";
}
