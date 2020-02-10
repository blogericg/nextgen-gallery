<?php

/**
 * Initializes the individual endpoint controllers
 */
class C_NextGen_Rest_V1
{
    public static $namespace = 'ngg/v1';

    public function __construct()
    {
        /*
         * Registers the following endpoints:
         *
         * /albums
         */
        $albums = new C_NextGen_Rest_V1_Albums();
        $albums->register_routes();

        /*
         * Registers the following endpoints:
         *
         * /galleries
         * /galleries/(?P<id>\d+)
         */
        $galleries = new C_NextGen_Rest_V1_Galleries();
        $galleries->register_routes();

        /* Registers the following endpoints:
         *
         * /galleries/(?P<id>\d+)/images
         */
        $images = new C_NextGen_Rest_V1_Images();
        $images->register_routes();
    }
}