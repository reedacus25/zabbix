<?php
/*
** Zabbix
** Copyright (C) 2001-2014 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

/**
 * Shows surrogate screen filled with simple graphs generated by selected item prototype or preview of item prototype.
 */
class CScreenLldSimpleGraph extends CScreenLldGraphBase {

	/**
	 * @var array
	 */
	protected $createdItemIds = array();

	/**
	 * @var array
	 */
	protected $itemPrototype = null;

	/**
	 * Adds simple graphs to surrogate screen.
	 */
	protected function addSurrogateScreenItems() {
		$createdItemIds = $this->getCreatedItemIds();
		$this->addSimpleGraphsToSurrogateScreen($createdItemIds);
	}

	/**
	 * Retrieves items created for item prototype given as resource for this screen item
	 * and returns array of the item IDs.
	 *
	 * @return array
	 */
	protected function getCreatedItemIds() {
		if (!$this->createdItemIds) {
			$hostId = $this->getCurrentHostId();

			// get all created (discovered) items for current host
			$allCreatedItems = API::Item()->get(array(
				'output' => array('itemid'),
				'hostids' => array($hostId),
				'selectItemDiscovery' => array('itemid', 'parent_itemid'),
				'filter' => array('flags' => ZBX_FLAG_DISCOVERY_CREATED),
				'sortfield' => 'name'
			));

			$itemPrototypeId = $this->getItemPrototypeId();

			if ($itemPrototypeId) {
				// collect those item IDs where parent item is item prototype selected for this screen item as resource
				foreach ($allCreatedItems as $item) {
					if ($item['itemDiscovery']['parent_itemid'] == $itemPrototypeId) {
						$this->createdItemIds[] = $item['itemid'];
					}
				}
			}
		}

		return $this->createdItemIds;
	}

	/**
	 * Makes and adds simple item graph items to surrogate screen from given item IDs.
	 *
	 * @param array $itemIds
	 */
	protected function addSimpleGraphsToSurrogateScreen(array $itemIds) {
		$screenItemTemplate = $this->getScreenItemTemplate(SCREEN_RESOURCE_SIMPLE_GRAPH);

		$screenItems = array();
		foreach ($itemIds as $itemId) {
			$screenItem = $screenItemTemplate;

			$screenItem['resourceid'] = $itemId;
			$screenItem['screenitemid'] = $this->screenitem['screenitemid'].'_'.$itemId;
			$screenItem['url'] = $this->screenitem['url'];

			$screenItems[] = $screenItem;
		}

		$this->addItemsToSurrogateScreen($screenItems);
	}

	/**
	 * @inheritdoc
	 */
	protected function getHostIdFromScreenItemResource() {
		$itemPrototype = $this->getItemPrototype();

		return $itemPrototype['discoveryRule']['hostid'];
	}

	/**
	 * @inheritdoc
	 */
	protected function getPreview() {
		$itemPrototype = $this->getItemPrototype();

		$queryParameters = array(
			'items' => array($itemPrototype),
			'period' => 3600,
			'legend' => 1,
			'width' => $this->screenitem['width'],
			'height' => $this->screenitem['height'],
			'name' => $itemPrototype['hosts'][0]['name'].NAME_DELIMITER.$itemPrototype['name']
		);

		$src = 'chart3.php?'.http_build_query($queryParameters);

		$img = new CImg($src);

		return $img;
	}

	/**
	 * Return item prototype ID of either item prototype selected in configuration, or, if dynamic mode is enabled,
	 * try to find item prototype with same key in selected host.
	 *
	 * @return string
	 */
	protected function getItemPrototypeId() {
		if ($this->screenitem['dynamic'] == SCREEN_DYNAMIC_ITEM && $this->hostid) {
			$currentItemPrototype = API::ItemPrototype()->get(array(
				'output' => array('name', 'key_'),
				'itemids' => array($this->screenitem['resourceid'])
			));
			$currentItemPrototype = reset($currentItemPrototype);

			$selectedHostItemPrototype = API::ItemPrototype()->get(array(
				'output' => array('itemid'),
				'hostids' => array($this->hostid),
				'filter' => array('key_' => $currentItemPrototype['key_'])
			));

			if ($selectedHostItemPrototype) {
				$selectedHostItemPrototype = reset($selectedHostItemPrototype);
				$itemPrototypeId = $selectedHostItemPrototype['itemid'];
			}
			else {
				$itemPrototypeId = null;
			}
		}
		else {
			$itemPrototypeId = $this->screenitem['resourceid'];
		}

		return $itemPrototypeId;
	}

	/**
	 * @inheritdoc
	 */
	protected function mustShowPreview() {
		$createdItemIds = $this->getCreatedItemIds();

		if ($createdItemIds) {
			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * Return item prototype used by this screen element.
	 *
	 * @return array
	 */
	protected function getItemPrototype() {
		if (!$this->itemPrototype) {
			$itemPrototype = API::ItemPrototype()->get(array(
				'output' => array('name'),
				'itemids' => array($this->getItemPrototypeId()),
				'selectHosts' => array('name'),
				'selectDiscoveryRule' => array('hostid')
			));
			$this->itemPrototype = reset($itemPrototype);
		}

		return $this->itemPrototype;
	}
}
