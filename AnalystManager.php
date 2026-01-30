<?php

class AnalystManager
{
    private $analysts = [];

    public function __construct($jsonFile)
    {
        if (file_exists($jsonFile)) {
            $json = file_get_contents($jsonFile);
            $this->analysts = json_decode($json, true) ?: [];
        }
    }

    public function getAll()
    {
        return $this->analysts;
    }

    public function findByName($name)
    {
        $results = [];
        foreach ($this->analysts as $analyst) {
            if (stripos($analyst['name'], $name) !== false) {
                $results[] = $analyst;
            }
        }
        return $results;
    }

    public function findByTrelloUsername($username)
    {
        foreach ($this->analysts as $analyst) {
            // Check trello_username or trello_id if you had it
            if (isset($analyst['trello_username']) && strcasecmp($analyst['trello_username'], $username) === 0) {
                return $analyst;
            }
        }
        return null;
    }

    // Fallback: match by full name if username isn't mapped perfectly
    public function findByTrelloFullName($fullName)
    {
        foreach ($this->analysts as $analyst) {
            if (stripos($analyst['name'], $fullName) !== false) {
                return $analyst;
            }
        }
        return null;
    }
}
