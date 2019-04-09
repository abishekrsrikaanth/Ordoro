<?php


namespace Ordoro\Core\Contracts;


interface Requestable
{
    public function send($method, $url, array $data = []);
}