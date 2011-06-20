<?php

/**
 * @file classes/stageAssignment/StageAssignmentDAO.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StageAssignmentDAO
 * @ingroup stageAssignment
 * @see StageAssignment
 *
 * @brief Operations for retrieving and modifying StageAssignment objects.
 */

import('classes.stageAssignment.StageAssignment');

class StageAssignmentDAO extends DAO {
	/**
	 * Retrieve StageAssignments by submission and stage IDs.
	 * @param $submissionId int
	 * @param $stageId int (optional)
	 * @param $userGroupId int (optional)
	 * @param $userId int (optional)
	 * @return DAOResultFactory StageAssignment
	 */
	function getBySubmissionAndStageId($submissionId, $stageId = null, $userGroupId = null, $userId = null) {
		return $this->_getByIds($submissionId, $stageId, $userGroupId, $userId);
	}

	/**
	 * Test if an editor or a series editor is assigned to the submission
	 * This test is used to determine what grid to place a submission into,
	 * and to know if the review stage can be started.
	 * @param $submissionId (int) The id of the submission being tested.
	 * @param $stageId (int) The id of the stage being tested.
	 * @return bool
	 */
	function editorAssignedToSubmission($submissionId, $stageId) {
		$result =& $this->retrieve(
					'SELECT COUNT(*)
					FROM stage_assignments sa JOIN user_groups ug ON (sa.user_group_id = ug.user_group_id)
					WHERE sa.stage_id = ? AND ug.role_id IN (?, ?)',
					array($stageId, ROLE_ID_PRESS_MANAGER, ROLE_ID_SERIES_EDITOR)
					);
		$returner = isset($result->fields[0]) && $result->fields[0] > 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Fetch a stageAssignment by symbolic info, building it if needed.
	 * @param $submissionId int
	 * @param $stageId int
	 * @param $userGroupId int
	 * @param $userId int
	 * @return StageAssignment
	 */
	function build($submissionId, $stageId, $userGroupId, $userId) {

		// If one exists, fetch and return.
		$stageAssignment = $this->_getByIds($submissionId, $stageId, $userGroupId, $userId, true);
		if ($stageAssignment) return $stageAssignment;

		// Otherwise, build one.
		unset($stageAssignment);
		$stageAssignment = $this->newDataObject();
		$stageAssignment->setSubmissionId($submissionId);
		$stageAssignment->setStageId($stageId);
		$stageAssignment->setUserGroupId($userGroupId);
		$stageAssignment->setUserId($userId);
		$this->insertObject($stageAssignment);
		return $stageAssignment;
	}

	/**
	 * Determine if a stageAssignment exists
	 * @param $submissionId int
	 * @param $stageId int
	 * @param $userGroupId int
	 * @param $userId int
	 * @return boolean
	 */
	function stageAssignmentExists($submissionId, $stageId, $userGroupId, $userId) {
		$stageAssignment = $this->_getByIds($submissionId, $stageId, $userGroupId, $userId, true);
		return ($stageAssignment)?true:false;
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return StageAssignmentEntry
	 */
	function newDataObject() {
		return new StageAssignment();
	}

	/**
	 * Internal function to return an StageAssignment object from a row.
	 * @param $row array
	 * @return StageAssignment
	 */
	function _fromRow(&$row) {
		$stageAssignment = $this->newDataObject();

		$stageAssignment->setSubmissionId($row['submission_id']);
		$stageAssignment->setStageId($row['stage_id']);
		$stageAssignment->setUserId($row['user_id']);
		$stageAssignment->setUserGroupId($row['user_group_id']);
		$stageAssignment->setDateAssigned($row['date_assigned']);

		return $stageAssignment;
	}

	/**
	 * Insert a new StageAssignment.
	 * @param $stageAssignment StageAssignment
	 * @return bool
	 */
	function insertObject(&$stageAssignment) {
		return $this->update(
				sprintf('INSERT INTO stage_assignments
				(submission_id, stage_id, user_group_id, user_id, date_assigned)
				VALUES
				(?, ?, ?, ?, %s)',
				$this->datetimeToDB(Core::getCurrentDate())),
			array(
				$stageAssignment->getSubmissionId(),
				$stageAssignment->getStageId(),
				$this->nullOrInt($stageAssignment->getUserGroupId()),
				$this->nullOrInt($stageAssignment->getUserId())
			)
		);
	}

	/**
	 * Delete a StageAssignment.
	 * @param $stageAssignment StageAssignment
	 * @return int
	 */
	function deleteObject($stageAssignment) {
		return $this->deleteByAll(
				$stageAssignment->getSubmissionId(),
				$stageAssignment->getStageId(),
				$stageAssignment->getUserGroupId(),
				$stageAssignment->getUserId()
			);
	}

	/**
	 * Delete a stageAssignment by matching on all fields.
	 * @param $submissionId int
	 * @param $stageId int
	 * @param $userGroupId int
	 * @param $userId int
	 * @return boolean
	 */
	function deleteByAll($submissionId, $stageId, $userGroupId, $userId) {
		return $this->update('DELETE FROM stage_assignments
					WHERE submission_id = ?
						AND stage_id = ?
						AND user_group_id = ?
						AND user_id = ?',
				array((int) $submissionId, (int) $stageId, (int) $userGroupId, (int) $userId));
	}

	/**
	 * Retrieve a stageAssignment by submission and stage IDs.
	 * Private method that holds most of the work.
	 * serves two purposes: returns a single assignment or returns a factory,
	 * depending on the calling context.
	 * @param $submissionId int
	 * @param $stageId int optional
	 * @param $userGroupId int optional
	 * @param $userId int optional
	 * @param $single bool specify if only one stage assignment (default is a ResultFactory)
	 * @return StageAssignment or ResultFactory
	 */
	function _getByIds($submissionId, $stageId = null, $userGroupId = null, $userId = null, $single = false) {
		$params = array((int) $submissionId);
		if (isset($stageId)) $params[] = (int) $stageId;
		if (isset($userGroupId)) $params[] = (int) $userGroupId;
		if (isset($userId)) $params[] = (int) $userId;

		$result =& $this->retrieve(
			'SELECT * FROM stage_assignments
			WHERE submission_id = ?' .
			(isset($stageId) ? ' AND stage_id = ?' : '') .
			(isset($userGroupId) ? ' AND user_group_id = ?' : '') .
			(isset($userId)?' AND user_id = ? ' : ''),
			$params
		);

		$returner = null;
		if ( $single ) {
				// fall four parameters must be specified for a single record to be returned
				if ( count($params !== 4 ) ) return false;
				// no matches were found.
				if ( $result->RecordCount() == 0) return false;
				$returner =& $this->_fromRow($result->GetRowAssoc(false));
				$result->Close();
		} else {
			// In any other case, return a list of all assignments
            $returner = new DAOResultFactory($result, $this, '_fromRow');
		}
		return $returner;
	}

}

?>