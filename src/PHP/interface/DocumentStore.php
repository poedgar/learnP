<?php

/**
 * How exactly does this work if the addDocument() method only accepts instances of the Documentable class?
 * The type hint
 * The name ending in -able strongly suggests it's an interface, following the common PHP convention (Countable, ArrayAccess, JsonSerializable, etc.). 
 * Any class that implements Documentable can be passed — regardless of what else it does. So the method accepts a contract: "give me anything that promises to behave like a document."
*/

class DocumentStore
{
    protected $data = [];

    public function addDocument(Documentable $document)
    {
        $key = $document->getId();
        $value = $document->getContent();
        $this->data[$key] = $value;
    }

    public function getDocuments()
    {
        return $this->data;
    }
}
