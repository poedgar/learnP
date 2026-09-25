<?php

/**

Global namespace
If you reference a class, interface, function, or constant without a namespace, PHP assumes the class, interface, function, or constant lives in the current namespace. If this assumption is wrong, PHP attempts to resolve the class, interface, function, or constant. If you need to reference a namespaced class, interface, function, or constant inside another namespace, you must use the fully qualified PHP class name (name‐ space + class name). You can type the fully qualified PHP class name, or you can import the code into the current namespace with the use keyword.
Some code might not have a namespace and, therefore, lives in the global namespace. The native Exception class is a good example. You can reference globally namespaced code inside another namespace by prepending a \ character to the class, interface, function, or constant name. For example, the \My\App\Foo::doSomething() method in Example 2-4 fails because PHP searches for a \My\App\Exception class that does not exist.

Instead, add a \ prefix to the Exception class name, as shown in Example 2-5. This tells PHP to look for the Exception class in the global namespace instead of the cur‐ rent namespace.
*/

namespace My\App;

class Foo 
{
    public function doSomething()
    {
        try {
            throw new \Exception('An error occurred');
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}