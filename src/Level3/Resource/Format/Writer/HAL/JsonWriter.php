<?php

namespace Level3\Resource\Format\Writer\HAL;

use Level3\Resource\Format\Writer\JsonWriter as BaseJsonWriter;
use Level3\Resource\Resource;

class JsonWriter extends BaseJsonWriter
{
    const CONTENT_TYPE = 'application/hal+json';

    protected function resourceToArray(Resource $resource)
    {
        $data = $resource->getData();

        $this->transformResources($data, $resource);
        $this->transformLinks($data, $resource);
        $this->transformLinkedResources($data, $resource);

        return $data;
    }

    protected function transformLinks(&$array, Resource $resource)
    {
        if ($self = $resource->getSelfLink()) {
            $array['_links']['self'] = $self->toArray();
        }

        foreach ($resource->getAllLinks() as $rel => $links) {
            if (!is_array($links)) {
                $array['_links'][$rel] = $links->toArray();
            } else {
                foreach ($links as $link) {
                    $array['_links'][$rel][] = $link->toArray();
                }
            }
        }
    }

    protected function transformLinkedResources(&$array, Resource $resource)
    {
        foreach ($resource->getAllLinkedResources() as $rel => $links) {
            if (!is_array($links)) {
                $array['_links'][$rel] = $links->getSelfLink()->toArray();
            } else {
                foreach ($links as $link) {
                    $array['_links'][$rel][] = $link->getSelfLink()->toArray();
                }
            }
        }
    }

    protected function transformResources(&$array, Resource $resource)
    {
        $embedded = [];
        foreach ($resource->getAllResources() as $rel => $resources) {
            if ($resources instanceof Resource) {
                $this->doTransformSingleResource($array, $embedded, $rel, $resources);
            } else {
                $this->doTransformResources($array, $embedded, $rel, $resources);
            }
        }

        if ($embedded) {
            $array['_embedded'] = $embedded;
        }
    }

    private function doTransformSingleResource(&$array, &$embedded, $rel, Resource $resource)
    {
        if (!$resource->getUri()) {
            $array[$rel] = $this->resourceToArray($resource);
        } else {
            $embedded[$rel] = $this->resourceToArray($resource);
        }
    }

    private function doTransformResource(&$array, &$embedded, $rel, Resource $resource)
    {
        if (!$resource->getUri()) {
            $array[$rel][] = $this->resourceToArray($resource);
        } else {
            $embedded[$rel][] = $this->resourceToArray($resource);
        }
    }

    private function doTransformResources(&$array, &$embedded, $rel, Array $resources)
    {
        foreach ($resources as $resource) {
            $this->doTransformResource($array, $embedded, $rel, $resource);
        }
    }
}
