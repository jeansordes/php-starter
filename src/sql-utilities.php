<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/utilities.php';
loadDotEnv();

class DB
{
    // { <nom_requete> : { sql: string, args: array<string> } }
    function getQuery($key)
    {
        return [
            // SELECT ALL
            'select_all_users' => 'select * from users',
            // SELECT ONE
            'select_user_from_id_user' => 'select * from users where id_user = :id_user',
            'select_user_from_email' => 'select * from users where email = :email',
            // UPDATE
            'update_password_hash' => 'update users set password_hash = :new_password_hash where id_user = :id_user',
            'update_last_user_update' => 'update users set last_user_update = :new_date where id_user = :id_user',
            // INSERT
            'insert_user' => 'insert into users(email, user_role) values (:email, :user_role)',
            // DELETE
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
                $this->_db = new SQLite3(__DIR__ . '/sql/' . $_ENV['db_name'] . '.db');
                $res = $this->_db->query("select name from sqlite_master");
                if (!$res->fetchArray()) {
                    console_log("DB intialized");
                    if (!$this->_db->exec(file_get_contents(__DIR__ . '/sql/init_sqlite3.sql'))) {
                        throw new \Exception($this->_db->lastErrorMsg());
                    }
                }
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

    public function queryNamedQuery($request_name)
    {
        return $this->query($this->getQuery($request_name));
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
                return $this->_sqlite3_result->fetchArray();
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
