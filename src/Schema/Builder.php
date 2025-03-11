<?php

declare(strict_types=1);

namespace PDPhilip\Elasticsearch\Schema;

use Closure;
use Exception;
use PDPhilip\Elasticsearch\Connection;
use PDPhilip\Elasticsearch\DSL\Results;

class Builder
{
    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    // ----------------------------------------------------------------------
    //  View Index Meta
    // ----------------------------------------------------------------------

    public function overridePrefix($value): Builder
    {
        $this->connection->setIndexPrefix($value);

        return $this;
    }

    public function getSettings($index): array
    {
        $this->connection->setIndex($index);

        return $this->connection->indexSettings($this->connection->getIndex());
    }

    public function getIndex($index): array
    {
        if ($this->hasIndex($index)) {
            $this->connection->setIndex($index);

            return $this->connection->getIndices(false);
        }

        return [];

    }

    public function hasIndex($index): bool
    {
        $index = $this->connection->setIndex($index);

        return $this->connection->indexExists($index);
    }

    public function getIndices(): array
    {
        return $this->connection->getIndices(false);
    }

    // ----------------------------------------------------------------------
    //  Create Index
    // ----------------------------------------------------------------------

    public function create($index, Closure $callback): array
    {
        $this->builder('buildIndexCreate', tap(new IndexBlueprint($index), function ($blueprint) use ($callback) {
            $callback($blueprint);
        }));

        return $this->getIndex($index);
    }

    protected function builder($builder, IndexBlueprint $blueprint): void
    {
        $blueprint->{$builder}($this->connection);
    }

    // ----------------------------------------------------------------------
    // Reindex
    // ----------------------------------------------------------------------

    public function createIfNotExists($index, Closure $callback): array
    {
        if ($this->hasIndex($index)) {
            return $this->getIndex($index);
        }
        $this->builder('buildIndexCreate', tap(new IndexBlueprint($index), function ($blueprint) use ($callback) {
            $callback($blueprint);
        }));

        return $this->getIndex($index);
    }

    // ----------------------------------------------------------------------
    // Modify Index
    // ----------------------------------------------------------------------

    public function reIndex($from, $to): Results
    {
        return $this->connection->reIndex($from, $to);
    }

    // ----------------------------------------------------------------------
    // Delete Index
    // ----------------------------------------------------------------------

    public function modify($index, Closure $callback): array
    {
        $this->builder('buildIndexModify', tap(new IndexBlueprint($index), function ($blueprint) use ($callback) {
            $callback($blueprint);
        }));

        return $this->getIndex($index);
    }

    public function delete($index): bool
    {
        $this->connection->setIndex($index);

        return $this->connection->indexDelete();
    }

    // ----------------------------------------------------------------------
    // Index template
    // ----------------------------------------------------------------------

    public function deleteIfExists($index): bool
    {
        if ($this->hasIndex($index)) {
            $this->connection->setIndex($index);

            return $this->connection->indexDelete();
        }

        return false;
    }

    // ----------------------------------------------------------------------
    // Analysers
    // ----------------------------------------------------------------------

    public function createTemplate($name, Closure $callback)
    {
        // TODO
    }

    // ----------------------------------------------------------------------
    // Index ops
    // ----------------------------------------------------------------------

    public function setAnalyser($index, Closure $callback): array
    {
        $this->analyzerBuilder('buildIndexAnalyzerSettings', tap(new AnalyzerBlueprint($index), function ($blueprint) use ($callback) {
            $callback($blueprint);
        }));

        return $this->getIndex($index);
    }

    protected function analyzerBuilder($builder, AnalyzerBlueprint $blueprint): void
    {
        $blueprint->{$builder}($this->connection);
    }

    public function hasField($index, $field): bool
    {
        $index = $this->connection->setIndex($index);

        try {
            $mappings = $this->getMappings($index);
            $props = $mappings[$index]['mappings']['properties'];
            $props = $this->_flattenFields($props);
            $fileList = $this->_sanitizeFlatFields($props);
            if (in_array($field, $fileList)) {
                return true;
            }
        } catch (Exception $e) {

        }

        return false;

    }

    // ----------------------------------------------------------------------
    // Manual
    // ----------------------------------------------------------------------

    public function getMappings($index): array
    {
        $this->connection->setIndex($index);

        return $this->connection->indexMappings($this->connection->getIndex());
    }

    public function getFieldMapping(string $index, string|array $field, bool $raw = false): array
    {
        $this->connection->setIndex($index);

        return $this->connection->fieldMapping($this->connection->getIndex(), $field, $raw);
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    private function _flattenFields($array, $prefix = ''): array
    {

        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = $result + $this->_flattenFields($value, $prefix.$key.'.');
            } else {
                $result[$prefix.$key] = $value;
            }
        }

        return $result;
    }

    private function _sanitizeFlatFields($flatFields): array
    {
        $fields = [];
        if ($flatFields) {
            foreach ($flatFields as $flatField => $value) {
                $parts = explode('.', $flatField);
                $field = $parts[0];
                array_walk($parts, function ($v, $k) use (&$field, $parts) {
                    if ($v == 'properties') {
                        $field .= '.'.$parts[$k + 1];

                    }
                });
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public function hasFields($index, array $fields): bool
    {
        $index = $this->connection->setIndex($index);

        try {
            $mappings = $this->getMappings($index);
            $props = $mappings[$index]['mappings']['properties'];
            $props = $this->_flattenFields($props);
            $fileList = $this->_sanitizeFlatFields($props);
            $allFound = true;
            foreach ($fields as $field) {
                if (! in_array($field, $fileList)) {
                    $allFound = false;
                }
            }

            return $allFound;
        } catch (Exception $e) {
            return false;
        }

    }

    // ----------------------------------------------------------------------
    // Internal Laravel init migration catchers
    // *Case for when ES is the only datasource
    // ----------------------------------------------------------------------

    public function dsl($method, $params): Results
    {
        return $this->connection->indicesDsl($method, $params);
    }

    // ----------------------------------------------------------------------
    // Builders
    // ----------------------------------------------------------------------

    public function flatten($array, $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = $result + $this->flatten($value, $prefix.$key.'.');
            } else {
                $result[$prefix.$key] = $value;
            }
        }

        return $result;
    }

    public function hasTable($table): array
    {
        return $this->getIndex($table);
    }
}
