<?php
require_once("Config.class.php");

class Fetcher
{
    const FOLLOWER_LIST   = "https://api.twitter.com/1.1/followers/ids.json";
    const USER_LOOKUP     = "https://api.twitter.com/1.1/users/lookup.json";
    const OAUTH_TOKEN     = "https://api.twitter.com/oauth2/token";

    private $username;
    private $user;
    private $follower_count;
    private $followers;

    private $bearer;
    private $utc_offset;

    public static function user($username) {
        $instance = new self();

        $instance->username        = $username;
        $instance->generateBearer();
        $instance->user            = $instance->lookupUser($username)[0];
        $instance->utc_offset      = $instance->user->utc_offset;
        $instance->follower_count  = $instance->user->followers_count;
        $instance->pages           = ceil($instance->getFollowerCount()/5000);
        $instance->fetchFollowers();

        return $instance;
    }

    public function Fetcher() {
        $this->generateBearer();
    }

    public function lookupUser($username) {
        if (substr($username, 0, 3) == "[#]") {
            $search = "user_id";
            $username = substr($username, 3);
        } else {
            $search = "screen_name";
        }
        $ch = curl_init();
 
        curl_setopt($ch, CURLOPT_URL, Fetcher::USER_LOOKUP . "?" . $search . "=" . $username);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->getBearer()));

        $json = json_decode(curl_exec($ch));
        curl_close($ch);
        if ($json->errors != null) {
            $this->error("User does not exist.");
        }
        return $json;
    }

    private function generateBearer() {
        $ch = curl_init();

        $postfields = http_build_query(
            array(
                "grant_type" => "client_credentials"
            )
        );
 
        curl_setopt($ch, CURLOPT_URL, Fetcher::OAUTH_TOKEN);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . Config::getBase64()));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

        $json = json_decode(curl_exec($ch));
        curl_close($ch);

        if ($json->access_token == null) {
            $this->error($json->errors[0]);
        }

        $this->bearer = $json->access_token;
    }

    private function fetchFollowers() {
        // Start at page 1
        $cursor = -1;

        for ($a = 0; $a < $this->getPages(); $a++) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, Fetcher::FOLLOWER_LIST . "?cursor=" . $cursor . "&screen_name=" . $this->getUsername() . "&skip_status=true&include_user_entities=false&count=5000");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->getBearer()));

            $json = json_decode(curl_exec($ch));
            curl_close($ch);
            if ($json->errors != null) {
                // If there was an error and no users have been fetched yet, it is a real error
                if ($a == 0) {
                    $this->error($json->errors[0]);
                // Else we have a partial fetching and can output what we got before being stopped
                } else {
                    break;
                }
            }
            foreach ($json->ids as $user) {
                $this->followers[] = $user;
            }

            // Update next cursor
            $cursor = $json->next_cursor;
        }
    }

    private function error($data) {
        if (is_object($data)) {
            die("Error " . $data->code . ": " . $data->message . " [" . (($data->label == null) ? "NULL" : $data->label) . "].\n");
        }
        die("Error: " . $data . "\n");
    }

    public function getUsername() { return $this->username; }
    public function getBearer() { return $this->bearer; }
    public function getOffset() { return $this->utc_offset; }
    public function getFollowerCount() { return $this->follower_count; }
    public function getPages() { return $this->pages; }
    public function getFollowers() { return $this->followers; }
}
?>
