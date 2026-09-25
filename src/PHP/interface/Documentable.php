<?php

/**
 * This interface definition says that any object implementing the Documentable inter‐ face must provide a public getId() method and a public getContent() method.
 */

interface Documentable
{
    public function getId();

    public function getContent();
}
