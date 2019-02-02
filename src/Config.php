<?php

namespace ObjectiveWP\Console;

class Config
{
    public static function getConfigPath() {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'organization.conf';
    }
}