<?php

namespace SLiMS\SearchEngine;

use SLiMS\DB;

trait SearchFilter
{
    public function getFilter($build = false)
    {
        $filter = [];

        # Publish Year
        list($min, $max) = $this->getYears();
        $filter[] = [
            'header' => __('Publish Year'),
            'name' => 'years',
            'type' => 'range',
            'min' => $min,
            'max' => $max,
            'from' => $min,
            'to' => $max
        ];

        # Availability
        $filter[] = [
            'header' => __('Availability'),
            'name' => 'availability',
            'type' => 'radio',
            'items' => [
                [
                    'value' => '1',
                    'label' => __('Available')
                ],
                [
                    'value' => '0',
                    'label' => __('On Loan')
                ]
            ]
        ];

        # Attachment
        $filter[] = [
            'header' => __('Attachment'),
            'name' => 'attachment',
            'type' => 'checkbox',
            'items' => [
                [
                    'value' => '0',
                    'label' => __('PDF')
                ],
                [
                    'value' => '1',
                    'label' => __('Audio')
                ],
                [
                    'value' => '2',
                    'label' => __('Video')
                ]
            ]
        ];

        # Collection type
        $filter[] = [
            'header' => __('Collection Type'),
            'name' => 'collType',
            'type' => 'checkbox',
            'items' => $this->getCollectionType()
        ];

        # GMD
        $filter[] = [
            'header' => __('General Material Designation'),
            'name' => 'gmd',
            'type' => 'checkbox',
            'items' => $this->getGMD()
        ];

        # Location
        $filter[] = [
            'header' => __('Location'),
            'name' => 'location',
            'type' => 'checkbox',
            'items' => $this->getLocation()
        ];

        # Language
        $filter[] = [
            'header' => __('Language'),
            'name' => 'lang',
            'type' => 'checkbox',
            'items' => $this->getLanguage()
        ];

        if ($build) return $this->buildFilter($filter);
        return $filter;
    }

    private function getYears()
    {
        $query = DB::getInstance()->query("select min(publish_year), max(publish_year) from biblio");
        return $query->fetch(\PDO::FETCH_NUM);
    }

    private function getCollectionType()
    {
        $query = DB::getInstance()->query("select coll_type_id `value`, coll_type_name `label` from mst_coll_type");
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getGMD()
    {
        $query = DB::getInstance()->query("select gmd_id `value`, gmd_name `label` from mst_gmd");
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getLocation()
    {
        $query = DB::getInstance()->query("select location_id `value`, location_name `label` from mst_location");
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getLanguage()
    {
        $query = DB::getInstance()->query("select language_id `value`, language_name `label` from mst_language");
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function buildFilter($filters): string
    {
        // get filter from url
        $filterStr = \utility::filterData('filter', 'get', false, true, true);
        $filterArr = json_decode($filterStr, true);

        $str = '<form id="search-filter"><ul class="list-group list-group-flush">';
        foreach ($filters as $index => $filter) {
            if ($index < 1) {
                $str .= '<li class="list-group-item bg-transparent pl-0 border-top-0">';
            } else {
                $str .= '<li class="list-group-item bg-transparent pl-0">';
            }

            $str .= <<<HTML
                <div class="d-flex justify-content-between align-items-center cursor-pointer" data-toggle="collapse" data-target="#collapse-{$index}">
                    <strong class="text-sm">{$filter['header']}</strong>
                    <i class="dropdown-toggle"></i>
                </div>
                <div class="collapse show text-sm" id="collapse-{$index}"><div class="mt-2">
HTML;

            $value = $filterArr[$filter['name']] ?? null;

            switch ($filter['type']) {
                case 'range':
                    list($from, $to) = is_null($value) ? [$filter['from'], $filter['to']] : explode(';', $value);
                    $str .= <<<HTML
                        <input type="text" class="input-slider" name="{$filter['name']}" value=""
                               data-type="double"
                               data-min="{$filter['min']}"
                               data-max="{$filter['max']}"
                               data-from="{$from}"
                               data-to="{$to}"
                               data-grid="true"
                        />
HTML;
                    break;

                case 'radio':
                case 'checkbox':
                    foreach ($filter['items'] as $idx => $item) {
                        $item_index = md5($filter['header'] . $item['value']);

                        if ($idx == 4) {
                            # open collapse items wrapper
                            $str .= '<div class="collapse" id="seeMore-' . $index . '">';
                        }

                        $filter_name = $filter['name'];
                        if($filter['type'] == 'checkbox') {
                            $filter_name .= '['.$idx.']';
                            $value = $filterArr[$filter['name'].'['.$idx.']'] ?? null;
                        }

                        $checked = $value == $item['value'] ? 'checked' : '';

                        $str .= <<<HTML
                            <div class="form-check">
                                <input class="form-check-input" name="{$filter_name}" type="{$filter['type']}" 
                                    id="item-{$item_index}" value="{$item['value']}" {$checked}>
                                <label class="form-check-label" for="item-{$item_index}">{$item['label']}</label>
                            </div>
HTML;
                    }
                    if (count($filter['items']) > 4) {
                        # close collapse items wrapper
                        $str .= '</div>';
                        $str .= '<a class="d-block mt-2" data-toggle="collapse" href="#seeMore-' . $index . '">' . __('See More') . '</a>';
                    }
                    break;
            }

            $str .= '</div></div></li>';
        }
        $str .= '</ul></form>';
        return $str;
    }
}