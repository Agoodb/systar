<?php
class Project_model extends BaseItem_model{
	
	function __construct(){
		parent::__construct();
		$this->table='project';
		$this->fields=array_merge($this->fields,array(
			'type'=>'project',
			'num'=>NULL,//编号
			'active'=>true,//是否有效
			'first_contact'=>NULL,//首次接洽时间
			'time_contract'=>NULL,//签约时间
			'end'=>NULL,//（预估）完结时间
			'quote'=>NULL,//报价
			'focus'=>NULL,//焦点
			'summary'=>NULL,//概况
			'comment'=>NULL,//备注
		));
	}
	
	function match($part_of_name){
		
		$this->db->select('project.id,project.type,project.num,project.name')
			->from('project')
			->where("project.company = {$this->company->id} AND project.display = 1 AND (name LIKE '%$part_of_name%' OR num LIKE '%$part_of_name%')",NULL,FALSE)
			->order_by('project.id','desc');
		
		return $this->db->get()->result_array();
	}

	function add($data=array()){
		
		foreach(array('first_contact','time_contract','end') as $date_field){
			if(empty($data[$date_field])){
				$data[$date_field]=NULL;
			}
		}
		
		$data['active']=true;
		
		$new_project_id=parent::add($data);
		
		return $new_project_id;
	}
	
	function update($id,array $data){
		
		foreach(array('first_contact','time_contract','end') as $date_field){
			if(isset($data[$date_field]) && $data[$date_field]===''){
				$data[$date_field]=NULL;
			}
		}
		
		return parent::update($id,$data);
	}
	
	function getCompiledPeople($project_id){
		
		$people=$this->people->getList(array('in_project'=>$project_id));
		$compiled='';
		foreach($people as $person){
			$compiled.='<span title="'.$person['role'].'"><a href="#'.$person['type'].'/'.$person['id'].'">'.$person['abbreviation'].'</a></span> ';
		}
		
		return $compiled;
	}
	
	function getCompiledPeopleRoles($project_id,$people_id){
		
		$roles=$this->getPeopleRoles($project_id, $people_id);
		
		$compiled='';
		foreach($roles as $role){
			$compiled.='<span role="'.$role['role'].'">'.$role['role'];
			if($role['weight']){
				$compiled.='('.($role['weight']*100).'%)';
			}
			$compiled.='</span> ';
		}
		
		return $compiled;
	}
	
	/**
	 * 获得一个项目下某个相关人员或所有人员的所有角色和其他属性
	 * @param int $project_id
	 * @param int $people_id, optional
	 * @return 
	 * array(
	 *	array(
	 *		role=>role_name
	 *		weight=>weight_in_role
	 *	),
	 *	...
	 * )
	 * or if people_id unspecified:
	 * array(
	 *	people_id=>array(
	 *		array(
	 *			role=>role_name
	 *			weight=>weight_in_role
	 *		)
	 *		...
	 *	)
	 *	...
	 * )
	 */
	function getPeopleRoles($project_id,$people_id=NULL){
		$this->db->from('project_people')
			->where(array('project'=>intval($project_id)))
			->select('role,weight');
		
		if($people_id){
			$this->db->where(array('people'=>intval($people_id)));
		}else{
			$this->db->select('people');
		}
		
		$result_array=$this->db->get()->result_array();
		
		if($people_id){
			return $result_array;
		}else{
			$people_roles=array();
			foreach($result_array as $row){
				$people_roles[$row['people']][]=$row;
			}
			return $people_roles;

		}
	}
	
	function removePeopleRole($project_id,$people_id,$role){
		return $this->db->delete('project_people',array(
			'project'=>$project_id,
			'people'=>$people_id,
			'role'=>$role
		));
	}
	
	/**
	 * 获得一个项目下某个角色或所有角色的所有相关人员的id和其他属性
	 * @param int $project_id
	 * @param string $role, optional
	 * @return 
	 * array(
	 *	array(
	 *		people=>people_id
	 *		weight=>weight_in_role
	 *	),
	 *	...
	 * )
	 * or if role unspecified:
	 * array(
	 *	role=>array(
	 *		array(
	 *			people=>people_id
	 *			weight=>weight_in_role
	 *		)
	 *		...
	 *	)
	 *	...
	 * )
	 */
	function getRolesPeople($project_id,$role=NULL){
		$this->db->from('project_people')
			->where(array('project'=>intval($project_id)))
			->select('people,weight');
		
		if($role){
			$this->db->where(array('role'=>$role));
		}else{
			$this->db->select('role');
		}
		
		$result_array=$this->db->get()->result_array();
		
		if($role){
			return $result_array;
		}else{
			$roles_people=array();
			foreach($result_array as $row){
				$roles_people[$row['role']][]=$row;
			}
			return $roles_people;

		}
	}
	
	/**
	 * @param array $labels
	 * @return array
	 */
	function getRelatedRoles($labels=NULL){
		$this->db->select('project_people.role, COUNT(*) AS hits',false)
			->from('project_people')
			->join('project',"project_people.project = project.id AND project.company = {$this->company->id}",'inner')
			->where('project_people.role IS NOT NULL',NULL,FALSE)
			->group_by('project_people.role')
			->order_by('hits', 'desc');
		
		if($labels){
			$this->db->join('project_label',"project_label.project = project_people.project AND project_label.label_name{$this->db->escape_array($labels)}",'inner');
		}
		
		$result=$this->db->get()->result_array();
		
		return array_sub($result,'role');
	}
	
	function addPeople($project_id,$people_id,$type=NULL,$role=NULL,$weight=NULL){
		
		$this->db->insert('project_people',array(
			'project'=>$project_id,
			'people'=>$people_id,
			'type'=>$type,
			'role'=>$role,
			'weight'=>$weight
		));
		
		return $this->db->insert_id();
	}
	
	function removePeople($project_id,$people_id){
		$people_id=intval($people_id);
		return $this->db->delete('project_people',array('project'=>$project_id,'people'=>$people_id));
	}
	
	function addDocument($project_id,$document_id){
		$project_id=intval($project_id);
		$document_id=intval($document_id);
		
		$data=array(
			'project'=>$project_id,
			'document'=>$document_id
		);
		
		$data+=uidTime(false);
		
		$this->db->insert('project_document',$data);
		
		return $this->db->insert_id();
	}
	
	function removeDocument($project_id,$document_id){
		return $this->db->delete('project_document',array('document'=>$document_id,'project'=>$project_id));
	}
	
	/**
	 * @param array $args
	 * people
	 *	role
	 * num
	 * active
	 * is_relative_of
	 * before
	 * time_contract
	 *	from
	 *	to
	 * first_contact
	 *	from
	 *	to
	 * count
	 * group
	 *	team
	 *	people
	 *		role
	 * 
	 */
	function getList(array $args=array()){

		if(isset($args['people'])){
			$where="project.id IN (SELECT project FROM project_people WHERE people{$this->db->escape_int_array($args['people'])}";
			if(isset($args['role'])){
				$where.=" AND role = '{$args['role']}'";
			}
			$where.=')';
			
			$this->db->where($where,NULL,FALSE);
		}
		
		if(isset($args['people_is_relative_of'])){
			$where="project.id IN (SELECT project FROM project_people WHERE people IN (SELECT relative FROM people_relationship WHERE people{$this->db->escape_int_array($args['people_is_relative_of'])})";
			if(isset($args['role'])){
				$where.=" AND role = '{$args['role']}'";
			}
			$where.=')';
			
			$this->db->where($where,NULL,FALSE);
		}

		if(isset($args['people_has_relative_like'])){
			$where="project.id IN (SELECT project FROM project_people WHERE people IN (SELECT people FROM people_relationship WHERE relative{$this->db->escape_int_array($args['people_has_relative_like'])})";
			if(isset($args['role'])){
				$where.=" AND role = '{$args['role']}'";
			}
			$where.=')';
			
			$this->db->where($where,NULL,FALSE);
		}

		if(isset($args['num'])){
			$this->db->like('project.num',$args['num']);
		}
		
		if(isset($args['active'])){
			$this->db->where('project.active',(bool)$args['active']);
		}
		
		if(isset($args['is_relative_of'])){
			$this->db->select('project.*')->select('relationship.relation')
				->join('project_relationship relationship',"relationship.relative = project.id",'inner')
				->where('relationship.project',intval($args['is_relative_of']));
		}
		
		if(isset($args['before'])){
			$this->db->where('project.id <',$args['before']);
		}
		
		foreach(array('first_contact','time_contract','end') as $date_field){
			if(isset($args[$date_field]['from']) && $args[$date_field]['from']){
				$this->db->where("TO_DAYS(project.$date_field) >= TO_DAYS('{$args[$date_field]['from']}')",NULL,FALSE);
			}

			if(isset($args[$date_field.'/from']) && $args[$date_field.'/from']){
				$this->db->where("TO_DAYS(project.$date_field) >= TO_DAYS('{$args[$date_field.'/from']}')",NULL,FALSE);
			}

			if(isset($args[$date_field]['to']) && $args[$date_field]['to']){
				$this->db->where("TO_DAYS(project.$date_field) <= TO_DAYS('{$args[$date_field]['to']}')",NULL,FALSE);
			}
			
			if(isset($args[$date_field.'/to']) && $args[$date_field.'/to']){
				$this->db->where("TO_DAYS(project.$date_field) <= TO_DAYS('{$args[$date_field.'/to']}')",NULL,FALSE);
			}
		}
		
		if(isset($args['group_by'])){
			if($args['group_by']==='team'){
				$this->db->join('team','team.id = project.team','inner')
					->group_by('project.team')
					->select('team.id `team`, team.name `team_name`');
			}
			
			if($args['group_by']==='people'){
				$this->db->join('project_people','project_people.project = project.id','inner')
					->join('people','people.id = project_people.people','inner')
					->group_by('project_people.people')
					->select('people.name AS people_name, people.id AS people');
				
				if(isset($args['role'])){
					$this->db->where('project_people.role',$args['role']);
				}
			}
		}
		
		return parent::getList($args);
		
	}
	
	function addRelative($project,$relative,$relation=NULL){
		$data=array(
			'project'=>intval($project),
			'relative'=>intval($relative),
			'relation'=>$relation
		);
		
		$this->db->replace('project_relationship',$data);
		
		return $this->db->insert_id();
	}
	
	function removeRelative($project_id,$relative_id){
		return $this->db->delete('project_relationship',array('project'=>intval($project_id),'relative'=>intval($relative_id)));
	}
	
}
?>