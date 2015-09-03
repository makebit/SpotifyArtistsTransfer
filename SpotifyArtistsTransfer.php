<?php

/**
 * SpotifyArtistTransfer
 * The aim of this tool is transfer all artists from one Spotify's account to another one.
 * Only two parameters are needed: token from the Sender Account and token from Receiver Account
 * You can obtain a new token here: https://developer.spotify.com/web-api/console/get-following/
 * Clicking on GET OAUTH TOKEN and selecting user-follow-read and user-follow-modify as scopes.
 * CreatedBy: makeb.it
 */
class SpotifyArtistsTransfer
{
    var $_urlGetFollowedArtists = "https://api.spotify.com/v1/me/following?type=artist&limit=50";
    var $_urlFollowArtists = "https://api.spotify.com/v1/me/following?type=artist&ids=";
    var $tokenAccountInput;
    var $tokenAccountOutput;
    var $followedArtists = [];
    var $totalFollowedArtists;
    var $ch;
    var $errors;

    const MAXIDS = 50;

    function __toString()
    {
        return (string)$this->totalFollowedArtists . ' ' . $this->errors;
    }


    function SpotifyArtistsTransfer($tokenAccountInput, $tokenAccountOutput)
    {
        $this->tokenAccountInput = $tokenAccountInput;
        $this->tokenAccountOutput = $tokenAccountOutput;
        $this->errors = $this->GetFollowedArtist();
        $this->errors = $this->errors.'+'.$this->FollowArtists();
    }

    private function curlCall($method, $link, $token)
    {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            "Authorization: Bearer $token"
        ));
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Spotify API Console v0.1');
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_URL, $link);
        $result = curl_exec($this->ch);
        curl_close($this->ch);
        return $array = json_decode($result, true);
    }

    private function GetFollowedArtist()
    {
        // if total number of artists is greater than 50, split the request
        $array = $this->curlCall('GET',$this->_urlGetFollowedArtists, $this->tokenAccountInput);
        if(isset($array['error']['status'])) return $array['error']['message'];
        $this->totalFollowedArtists = $array['artists']['total'];
        $i = 0;
        $noc = (int)($this->totalFollowedArtists/ self::MAXIDS) + 1;
        while ($i < $noc) {
            for ($j=0; $j<self::MAXIDS && isset($array['artists']['items'][$j]['id']); $j++) {
                array_push($this->followedArtists, $array['artists']['items'][$j]['id']);
            }
            $nextLink = null;
            if ($array['artists']['next'] != null) {
                $next = $array['artists']['cursors']['after'];
                $array = $this->curlCall('GET', $this->_urlGetFollowedArtists . "&after=" . $next, $this->tokenAccountInput);
            }
            $i++;
        }
        return '';
    }

    function PrintIDsArtists()
    {
        for ($i = 0; $i < $this->totalFollowedArtists; $i++) {
            echo $i . "# " . $this->followedArtists[$i] . "<br>";
        }
    }

    private function FollowArtists()
    {
        $count = 0; $i=0; $ids=''; $errors=null;
        $noc = (int)($this->totalFollowedArtists / self::MAXIDS) + 1;
        while ($i < $noc) {
            for ($j=$count; $j<$count+self::MAXIDS && $j<$this->totalFollowedArtists; $j++) {
                $ids=$ids.$this->followedArtists[$j].',';
            }
            $array = $this->curlCall('PUT', $this->_urlFollowArtists.substr($ids, 0, strlen($ids)-1), $this->tokenAccountOutput); // remove last ','
            if(isset($array['error']['status'])) $errors=$errors.'+'.$array['error']['message'];
            $count += self::MAXIDS; $i++; $ids='';
        }
        return $errors;
    }
}