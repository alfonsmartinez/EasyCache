<?php

namespace EasyCache;

use Doctrine\Common\Cache\CacheProvider;

class EasyCache
{

    private $cache;
    private $lifetime;
    private $list_name = '_cache_list_control_';

    public function __construct(CacheProvider $cache, $lifetime = 3600)
    {
        $this->lifetime = $lifetime;
        $this->cache = $cache;
    }

    public function issetCache($nombre)
    {
        return $this->cache->contains($nombre);
    }

    public function get($nombre)
    {
        return unserialize($this->cache->fetch($nombre));
    }

    public function set($nombre, $data, $lifetime = false)
    {
        $lifetime = ($lifetime) ? $lifetime : $this->lifetime;
        $this->cache->save($nombre, serialize($data), $lifetime);
        $this->addCacheList($nombre);
        return $this->get($nombre);
    }

    private function setList($list)
    {
        $this->cache->save($this->list_name, serialize($list), 0);
    }

    public function delete($nombre)
    {
        $list = $this->get($this->list_name);
        if (($key = array_search($nombre, $list)) !== false) {
            unset($list[$key]);
            $this->setList($list);
        }

        $this->cache->delete($nombre);
    }

    public function deleteAll()
    {
        $this->setList([]);
        $this->cache->flushAll();
    }

    private function addCacheList($nombre)
    {
        if (!$list = $this->get($this->list_name)) {
            $list = [];
        }
        if (!in_array($nombre, $list)) {
            $list[] = $nombre;
        }
        $this->setList($list);
    }

    public function deleteByPrefix($prefix)
    {
        if ($list = $this->get($this->list_name)) {
            $deletes = [];
            foreach ($list as $name) {
                if (preg_match('@^' . $prefix . '.*@', $name)) {
                    $deletes[] = $name;
                    $this->delete($name);
                }
            }
            $list = array_diff($list, $deletes);
            $this->setList($list);
        }
    }

    public function deleteByRegex($regex)
    {
        if ($list = $this->get($this->list_name)) {
            $deletes = [];
            foreach ($list as $name) {
                if (preg_match($regex, $name)) {
                    $deletes[] = $name;
                    $this->delete($name);
                }
            }
            $list = array_diff($list, $deletes);
            $this->setList($list);
        }
    }


}