<?php

namespace DFurnes\LaravelDuskMocks\Controllers;

use Illuminate\Http\Request;

class MockController
{
    /**
     * Set the given mock on the Dusk server.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function mock(Request $request)
    {
        $binding = $request->query('binding');
        $closure = $request->query('closure');

        $cookie = cookie('_dusk_mock:'.$binding, $closure, 60);

        return (new \Illuminate\Http\Response)->withCookie($cookie);
    }
}

