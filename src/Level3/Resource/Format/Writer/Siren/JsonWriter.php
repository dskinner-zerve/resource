<?php

namespace Level3\Resource\Format\Writer\Siren;

use Level3\Resource\Format\Writer\JsonWriter as BaseJsonWriter;
use Level3\Resource\Resource;
use Level3\Resource\Link;

class JsonWriter extends BaseJsonWriter
{
    const CONTENT_TYPE = 'application/vnd.siren+json';

    protected function resourceToArray(Resource $resource)
    {
        $data = array();

        $this->transformDataAndMetadata($data, $resource);
        $this->transformResources($data, $resource);
        $this->transformLinks($data, $resource);
        $this->transformLinkedResources($data, $resource);

        return $data;
    }

    protected function transformDataAndMetadata(&$array, Resource $resource)
    {
        $array['class'] = null;
        if ($key = $resource->getRepositoryKey()) {
            $array['class'] = explode('/', $key);
        }

        if ($title = $resource->getTitle()) {
            $array['title'] = $title;
        }

        $array['properties'] = $resource->getData();
    }

    protected function transformLinks(&$array, Resource $resource)
    {
        if ($self = $resource->getSelfLink()) {
            $array['links'][] = array(
                'rel' => 'self',
                'href' => $self->getHref()
            );
        }

        foreach ($resource->getAllLinks() as $rel => $links) {
            if (!is_array($links)) {
                $links = array($links);
            }

            foreach ($links as $link) {
                $array['links'][] = array(
                    'rel' => $rel,
                    'href' => $link->getHref()
                );
            }
        }
    }

    protected function transformLinkedResources(&$array, Resource $resource)
    {
        $embeddedLinks = $this->getHrefsFromEntities($array);

        foreach ($resource->getAllLinkedResources() as $rel => $linkedResources) {
            if (!is_array($linkedResources)) {
                $linkedResources = array($linkedResources);
            }

            foreach ($linkedResources as $linked) {
                $link = $linked->getSelfLink()->getHref();
                if (isset($embeddedLinks[$link])) {
                    continue;
                }

                $array['entities'][] = array(
                    'rel' => $rel,
                    'href' => $link
                );
            }
        }
    }

    private function getHrefsFromEntities($array)
    {
        $embeddedLinks = array();
        if (isset($array['entities'])) {
            foreach ($array['entities'] as $entity) {
                if (isset($entity['href'])) {
                    $embeddedLinks[$entity['href']] = true;
                }
            }
        }

        return $embeddedLinks;
    }

    protected function transformResources(&$array, Resource $resource)
    {
        foreach ($resource->getAllResources() as $rel => $resources) {
            if (!is_array($resources)) {
                $resources = array($resources);
            }

            foreach ($resources as $resource) {
                $array['entities'][] = $this->doTransformResource($array, $rel, $resource);
            }
        }
    }

    protected function doTransformResource(Array &$array, $rel, Resource $resource)
    {
        $data = $this->resourceToArray($resource);
        if (!$data['class']) {
            $data['class'] = array_merge($array['class'], array($rel));
        }

        $metadata = array();
        $metadata['rel'] = $rel;

        if ($resource->getUri()) {
            $metadata['href'] = $resource->getSelfLink()->getHref();
        }

        return array_merge($metadata, $data);
    }
}
