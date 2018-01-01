<?php
/*
 * This file is part of foxverse
 * Copyright (C) 2017 Steph Lockhomes, Billy Humphreys
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

require_once(__DIR__ . DIRECTORY_SEPARATOR . "../vendor/autoload.php");

class Core {
    public function generateLoginToken($length = 6) {
        $characters = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $charactersLength = strlen($characters);
        $randomString = "FV"; // Stands for foxverse

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
    
    public function generatePostId() {
        // Prod: 018307; Dev: 018101;
        $magic_url = pack("C*", 0x01, 0x83, 0x07);
        $urlenv_url = pack("C*", 0x00, 0x00);
        $magic2_url = pack("C*", 0x41);
        $unique_url = openssl_random_pseudo_bytes(10);
        $b64url_data = str_replace(
            array("+", "/", "="),
            array("-", "_", ""),
            base64_encode($magic_url . $urlenv_url . $magic2_url . $unique_url)
        );
        return $b64url_data;
    }

    public function initTwig($dir = __DIR__ . DIRECTORY_SEPARATOR . "../views/") {
        $loader = new Twig_Loader_Filesystem($dir);
        $twigEnv = new Twig_Environment($loader, array("debug" => true));
        $twigEnv->addExtension(new Twig_Extensions_Extension_I18n());
        $twigEnv->addGlobal("request", $_REQUEST);
        $twigEnv->addGlobal("session", $_SESSION);
        $twigEnv->addGlobal("server", $_SERVER);
        $bin2hex = new Twig_SimpleFilter("bin2hex", "bin2hex");
        $twigEnv->addFilter($bin2hex);
        return $twigEnv;
    }

    public function getMiiImage($name) {
        $ch = curl_init();
        $api = "https://accountws.nintendo.net/v1/api/";

        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                "X-Nintendo-Client-ID: " . getenv("CLIENT_ID"),
                "X-Nintendo-Client-Secret: " . getenv("CLIENT_SECRET")
            )
        ));

        curl_setopt($ch, CURLOPT_URL, $api . "admin/mapped_ids?input_type=user_id&output_type=pid&input=" . $name);
        $mapped_ids = new SimpleXMLElement(curl_exec($ch));
        if (!$mapped_ids->mapped_id->out_id) {
            return false;
        }

        $pid = $mapped_ids->mapped_id->out_id;
        curl_setopt($ch, CURLOPT_URL, $api . "miis?pids=" . $pid);
        $miis = new SimpleXMLElement(curl_exec($ch));
        curl_close($ch);

        foreach (json_decode(json_encode($miis), true)["mii"]["images"]["image"] as $a) {
            if ($a["type"] == "normal_face") {
                return $a["cached_url"];
            }
        }
    }
}