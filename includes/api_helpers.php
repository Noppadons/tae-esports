<?php
// /includes/api_helpers.php

function fetchApiData($url, $cache_file, $cache_time = 3600) {
    $cache_dir = __DIR__ . '/../api_cache/';
    if (!file_exists($cache_dir)) { mkdir($cache_dir, 0777, true); }
    $cache_path = $cache_dir . $cache_file;
    if (file_exists($cache_path) && (time() - filemtime($cache_path)) < $cache_time) {
        return json_decode(file_get_contents($cache_path), true);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $json_data = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($json_data, true);
    if (is_array($data) && !empty($data)) {
        file_put_contents($cache_path, $json_data);
    }
    return $data;
}

function getDota2Heroes() {
    $heroes_raw = fetchApiData('https://api.opendota.com/api/heroStats', 'hero_stats.json', 86400);
    $heroes_clean = [];
    if (is_array($heroes_raw)) {
        foreach ($heroes_raw as $hero) {
            $clean_hero = [];
            $clean_hero['id'] = $hero['id'] ?? 0;
            $clean_hero['name'] = $hero['name'] ?? ''; 
            $clean_hero['localized_name'] = $hero['localized_name'] ?? 'Unknown Hero';
            $clean_hero['primary_attr'] = $hero['primary_attr'] ?? '';
            $clean_hero['roles'] = $hero['roles'] ?? [];
            if (isset($hero['img'])) {
                $clean_hero['full_img_url'] = 'https://cdn.cloudflare.steamstatic.com' . $hero['img'];
            }
            $heroes_clean[] = $clean_hero;
        }
    }
    return $heroes_clean;
}

// --- ฟังก์ชันที่เพิ่มเข้ามาใหม่ ---
function getDota2Items() {
    return fetchApiData("https://api.opendota.com/api/constants/items", "const_items.json", 86400 * 7); // Cache for 7 days
}

function getDota2HeroAbilitiesAndTalents($hero_id, $hero_internal_name) {
    if(empty($hero_id) || empty($hero_internal_name)) return ['abilities' => [], 'talents' => []];

    // Abilities
    $all_hero_abilities_data = fetchApiData("https://api.opendota.com/api/constants/hero_abilities", "const_hero_abilities.json", 86400);
    $all_abilities_data = fetchApiData("https://api.opendota.com/api/constants/abilities", "const_abilities.json", 86400);
    $abilities = [];
    if (isset($all_hero_abilities_data[$hero_internal_name]['abilities'])) {
        foreach ($all_hero_abilities_data[$hero_internal_name]['abilities'] as $ability_name) {
            if (isset($all_abilities_data[$ability_name]) && !str_starts_with($ability_name, 'special_bonus')) {
                $abilities[] = $all_abilities_data[$ability_name]['dname'] ?? $ability_name;
            }
        }
    }

    // Talents
    $hero_talents_raw = fetchApiData("https://api.opendota.com/api/heroes/{$hero_id}/talents", "talents_{$hero_id}.json", 86400);
    $talents = [];
    if (is_array($hero_talents_raw)) {
        foreach($hero_talents_raw as $talent) {
             if(isset($talent['dname']) && isset($talent['level'])) {
                 $talents[] = "[Lv.{$talent['level']}] {$talent['dname']}";
             }
        }
    }
    
    return ['abilities' => $abilities, 'talents' => $talents];
}
?>