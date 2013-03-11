<?php
require_once "components" . DIRECTORY_SEPARATOR . "input" . DIRECTORY_SEPARATOR . "input.php";

class InputTest extends PHPUnit_Framework_TestCase {

	private $Input;
	
	public function setUp() {
		$this->Input = new Input();
	}
	
	/**
	 * @dataProvider inputPreFormatProvider
	 */
	public function testPreFormat($rules, $data, $formatted_data) {
		// Set the rules to test
		$this->Input->setRules($rules);
		
		// Attempt to validate $data against $rules
		$this->Input->validates($data);
		
		// Ensure that data is now modified such that is matches our expected $formatted_data
		$this->assertEquals($formatted_data, $data);
	}

	/**
	 * @dataProvider inputPostFormatProvider
	 */	
	public function testPostFormat($rules, $data, $formatted_data) {
		// Set the rules to test
		$this->Input->setRules($rules);
		
		// Attempt to validate $data against $rules
		$this->Input->validates($data);
		
		// Ensure that data is now modified such that is matches our expected $formatted_data
		$this->assertEquals($formatted_data, $data);		
	}
	
	/**
	 * @dataProvider inputValidationProvider
	 */
	public function testValidation($rules, $data) {
		// Set the rules to test
		$this->Input->setRules($rules);
		
		// Attempt to validate $data against $rules
		$this->assertEquals(true, $this->Input->validates($data));

	}

	public function inputPreFormatProvider() {
		return $this->getInputDataFormatting("pre_format");
	}

	public function inputPostFormatProvider() {
		return $this->getInputDataFormatting("post_format");
	}
	
	private function getInputDataFormatting($action) {
		
		$rule_sets = array(
			array(
				'name'=>array(
					'format'=>array(
						'rule'=>"isEmpty",
						'negate'=>true,
						$action=>"strtolower"
					)
				),
				'company'=>array(
					'format'=>array(
						'rule'=>"isEmpty",
						'negate'=>true,
						$action=>"strtoupper"
					)
				)
			),
			array(
				'name[]'=>array(
					'format'=>array(
						'rule'=>"isEmpty",
						'negate'=>true,
						$action=>"strtolower"
					)
				),
				'company[]'=>array(
					'format'=>array(
						'rule'=>"isEmpty",
						'negate'=>true,
						$action=>"strtoupper"
					)
				)
			)
		);
		
		$data_sets = array(
			array(
				'name'=>"Person Name",
				'company'=>"Company Name"
			),
			array(
				'name'=>array(
					'Person Name 1',
					'Person Name 2'
				),
				'company'=>array(
					'Company Name 1',
					'Company Name 2'
				)
			)
		);
		
		$formatted_data = $data_sets;
		
		$formatted_data[0]['name'] = strtolower($formatted_data[0]['name']);
		$formatted_data[0]['company'] = strtoupper($formatted_data[0]['company']);
		foreach ($formatted_data[1]['name'] as &$result) {
			$result = strtolower($result);
		}
		foreach ($formatted_data[1]['company'] as &$result) {
			$result = strtoupper($result);
		}
		
		$data = array();
		foreach ($rule_sets as $i => $value) {
			$data[] = array($rule_sets[$i], $data_sets[$i], $formatted_data[$i]);
		}
		
		return $data;
	}
	
	public function inputValidationProvider() {
		
		$rule_sets = array(
			// scalar
			array(
				'name'=>array(
					'format'=>array(
						'rule'=>"isEmpty",
						'negate'=>true,
						'message'=>"name can not be empty"
					)
				),
				'company'=>array(
					'format'=>array(
						'rule'=>"isEmpty",
						'message'=>"company must be empty"
					)
				)
			),
			// array
			array(
				'name[]'=>array(
					'format'=>array(
						'rule'=>"isEmpty",
						'negate'=>true,
						'message'=>"name can not be empty"
					)
				),
				'company[]'=>array(
					'format'=>array(
						'rule'=>"isEmpty",
						'message'=>"company must be empty"
					)
				)
			),
			// multi-dimensional array
			array(
				'data[name][]'=>array(
					'format'=>array(
						'rule'=>"isEmpty",
						'negate'=>true,
						'message'=>"name can not be empty"
					)
				),
				'data[company][]'=>array(
					'format'=>array(
						'rule'=>"isEmpty",
						'message'=>"company must be empty"
					)
				)
			),
			// alternative array
			array(
				'name[1]'=>array(
					'format'=>array(
						'rule'=>array(array($this, "callBackTestMethod")),
						'message'=>"name[1] can not be empty"
					)
				),
				'name[2]'=>array(
					'format'=>array(
						'rule'=>array(array($this, "callBackTestMethod")),
						'message'=>"name[2] must be empty"
					)
				)
			),
			// alternative multi-dimensional array
			array(
				'data[][name]'=>array(
					'format'=>array(
						'rule'=>"isEmpty",
						'negate'=>"true",
						'message'=>"name can not be empty"
					)
				),
				'data[][company]'=>array(
					'format'=>array(
						'rule'=>"isEmpty",
						'message'=>"company must be empty"
					)
				)
			)
		);
		
		$data_sets = array(
			array(
				'name'=>"Firstname Lastname",
				'company'=>""
			),
			array(
				'name'=>array(
					"Firstname Lastname",
					"Secondname Lastname",
					"Thirdname Lastname"
				),
				'company'=>array(
					"",
					"",
					""
				)
			),
			array(
				'data'=>array(
					'name'=>array(
						"Firstname Lastname",
						"Secondname Lastname",
						"Thirdname Lastname"
					),
					'company'=>array(
						"",
						"",
						""
					)
				)
			),
			array(
				'name'=>array(
					'1'=>"Firstname Lastname",
					'2'=>"Secondname Lastname"
				)
			),
			array(
				'data'=>array(
					array(
						'name'=>"Firstname Lastname",
						'company'=>""
					),
					array(
						'name'=>"Secondname Lastname",
						'company'=>""
					),
					array(
						'name'=>"Thirdname Lastname",
						'company'=>""
					)
				)
			)
		);
		
		$data = array();
		foreach ($rule_sets as $i => $set) {
			$data[] = array($rule_sets[$i], $data_sets[$i]);
		}
		
		return $data;
		
	}
	
	public function callBackTestMethod($value) {
		return true;
	}
}
?>