<?php

interface supported_meta {
    public function name();
    public function wrapped_class();
    public function defaults();
    public function can_use();
}

abstract class provided_meta implements supported_meta {
    public function wrapped_class() {
        if (preg_match('/(ues_\w+)_supported_meta/', get_class($this), $match)) {
            return $match[1];
        }

        throw new Exception('Could not find supported meta class');
    }

    public function can_use() {
        $context = get_context_instance(CONTEXT_SYSTEM);
        return has_capability('block/ues_meta_viewer:access', $context);
    }
}

class ues_user_supported_meta extends provided_meta {
    public function name() {
        return get_string('user', 'block_ues_meta_viewer');
    }

    public function defaults() {
        return array(
            'username', 'idnumber', 'firstname', 'lastname'
        );
    }
}

class ues_section_supported_meta extends provided_meta {
    public function name() {
        return get_string('section', 'block_ues_meta_viewer');
    }

    public function defaults() {
        return array(
            'id', 'idnumber', 'sec_number', 'courseid', 'semesterid', 'status'
        );
    }
}

class ues_course_supported_meta extends provided_meta {
    public function name() {
        return get_string('course', 'block_ues_meta_viewer');
    }

    public function defaults() {
        return array(
            'id', 'department', 'cou_number', 'fullname'
        );
    }
}

class ues_semester_suppported_meta extends provided_meta {
    public function name() {
        return get_string('semester', 'block_ues_meta_viewer');
    }

    public function defaults() {
        return array(
            'id', 'year', 'name', 'session_key', 'campus'
        );
    }
}

class ues_teacher_supported_meta extends provided_meta {
    public function name() {
        return get_string('teacher', 'block_ues_meta_viewer');
    }

    public function defaults() {
        return array(
            'id', 'userid', 'sectionid', 'status', 'primary_flag'
        );
    }
}

class ues_student_supported_meta extends provided_meta {
    public function name() {
        return get_string('student', 'block_ues_meta_viewer');
    }

    public function defaults() {
        return array(
            'id', 'userid', 'sectionid', 'status', 'credit_hours'
        );
    }
}
