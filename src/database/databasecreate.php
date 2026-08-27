<?php
// name: databasecreate.php
// location: src/database/databasecreate.php

function createDatabase($connect, $dbname)
	{
		$sql_file = __DIR__ . '/../../database/schema.sql'; 
        $seed_file = __DIR__ . '/../../database/seed.sql';
        $sql_commands = file_get_contents($sql_file);
        $seed_commands = file_get_contents($seed_file);

        $queries = array_merge(explode(';', $sql_commands), explode(';', $seed_commands));
        foreach ($queries as $query) {
            try {
                $query = trim($query);
                if ($query == "")
					continue;
				if(!$connect->query($query)) {
					// echo "query failed to run<br>";
					return 1;
				}
            } catch (Exception $e) {
                error_log("Database creation failed: " . $e->getMessage());
                echo "error while running query: $query <br>";
                return 1;
            }
        }
		return 0;
	}
?>