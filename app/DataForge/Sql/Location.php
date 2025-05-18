<?php

namespace App\DataForge\Sql;

use DataForge\Sql;

class Location extends Sql
{
    public function regions(&$data)
    {
        $query = Query('Regions');
        $query->select('list', 'r.name AS id, r.name');
        $query->select('total', 'COUNT(r.id) AS total');

        $query->from('regions AS r');

        $query->filter('r.flag = 1');
        $query->filterOptional('r.name = {request.region}');

        $query->order('r.name', 'ASC');

        return $query;
    }

    public function subregions(&$data)
    {
        $query = Query('SubRegions');
        $query->select('list', 'sr.name AS id, sr.name');
        $query->select('total', 'COUNT(sr.id) AS total');

        $query->from('subregions AS sr');
        $query->inner('regions AS r ON r.id=sr.region_id');

        $query->filter('sr.flag = 1');

        $query->filterAnyOneRequired('id Or region anyone must', [
            'sr.id = {request.id}',
            'r.name = {request.region}'
        ]);

        $query->order('sr.name', 'ASC');

        return $query;
    }

    public function countries(&$data)
    {
        $query = Query('Countries');
        $query->select('list', 'c.name AS id, c.name');
        $query->select('total', 'COUNT(c.id) AS total');

        $query->from('countries AS c');

        $query->filter('c.flag = 1');

        $query->filterAnyOneRequired('id Or region Or subregion anyone must', [
            'c.id = {request.id}',
            'c.region = {request.region}',
            'c.subregion = {request.subregion}',
            '{request.all}'
        ]);

        $query->order('c.name', 'ASC');

        return $query;
    }

    public function states(&$data)
    {
        $query = Query('States');
        $query->select('list', 's.name AS id, s.name');
        $query->select('total', 'COUNT(s.id) AS total');

        $query->from('states AS s');
        $query->inner('countries AS c ON c.id = s.country_id');

        $query->filter('s.flag = 1 && c.flag = 1');

        $query->filterAnyOneRequired('id Or country_id Or country_code Or country anyone must', [
            's.id = {request.id}',
            's.country_id = {request.country_id}',
            's.country_code = {request.country_code}',
            'c.name = {request.country}'
        ]);

        $query->order('s.name', 'ASC');

        return $query;
    }

    public function cities(&$data)
    {
        $query = Query('Cities');
        $query->select('list', 'ct.name AS id, ct.name');
        $query->select('total', 'COUNT(s.id) AS total');

        $query->from('cities AS ct');
        $query->inner('states AS s ON s.id=ct.state_id');
        $query->inner('countries AS c ON c.id = ct.country_id');

        $query->filter('s.flag = 1 && c.flag = 1');

        $query->filterAnyOneRequired('id Or country_id Or country_code Or country Or state_id Or state anyone must', [
            'ct.id = {request.id}',
            'ct.country_id = {request.country_id}',
            'ct.country_code = {request.country_code}',
            'ct.state_id = {request.state_id}',
            's.name = {request.state}',
            'c.name = {request.country}'
        ]);

        $query->order('ct.name', 'ASC');

        return $query;
    }



    public function create(&$data)
    {
        $query = Query('PaymentCreate');
        $query->insert('payments', [
            'subscription_id' => '{request.subscription_id}',
            'amount' => '{request.amount}',
            'status' => '{request.status}',
            'payment_date' => now(),
        ]);
        return $query;
    }
}
