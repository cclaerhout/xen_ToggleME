<?php

class Sedo_ToggleME_Model_GetNodes extends XenForo_Model
{
	public function getNodesOptions($selectedNodesIds = array())
	{
		$nodes = array();
		foreach ($this->getDbNodes() AS $node)
		{
			$nodes[] = array(
			'label' => $node['title'],
			'value' => $node['node_id'],
			'selected' => in_array($node['node_id'], $selectedNodesIds)
			);
		}

		return $nodes;
	}

	public function manageLanguageNodesOptions($langConfigs)
	{
		$xenNodes = $this->getDbNodes();
		
		foreach($langConfigs as $key => &$config)
		{
			if(!isset($config['nodes']))
			{
				unset($langConfigs[$key]);
			}
			else
			{
				$config['nodes'] = $this->getNodesOptions($config['nodes']);
			}
		}

		return $langConfigs;
	}

	public function getDbNodes()
	{
		return $this->_getDb()->fetchAll('
			SELECT node_id, title, node_type_id
			FROM xf_node
			WHERE node_type_id = ?
			ORDER BY node_id
		', 'Category');
	}
}