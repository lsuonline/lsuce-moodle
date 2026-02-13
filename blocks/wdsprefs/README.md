# Workday Student Faculty Preferences Block

The Workday Student Faculty Preferences Block (`block_wdsprefs`) is a powerful Moodle plugin designed to bridge the gap between Workday Student (SIS) and Moodle. It empowers instructors with a suite of self-service tools to manage their course shells, sections, and enrollments, providing flexibility beyond the standard automated integration.

## Requirements

*   **Moodle 4.3** or later.
*   **[enrol_workdaystudent]**: This block is dependent on the `enrol_workdaystudent` plugin. It relies on the core classes, settings, and enrollment logic provided by that plugin. **The block will not function without it.**

## Installation

1.  Ensure the `enrol_workdaystudent` plugin is installed and configured on your site.
2.  Install the `block_wdsprefs` plugin:
    *   **Git:** Clone the repository into your Moodle `blocks/` directory:
        ```bash
        git clone https://github.com/your-org/moodle-block_wdsprefs.git blocks/wdsprefs
        ```
    *   **Zip:** Extract the plugin archive to `blocks/wdsprefs`.
3.  Log in to your Moodle site as an administrator.
4.  Navigate to **Site administration > Notifications** to trigger the installation process.

## Configuration

The block primarily leverages the global configuration settings defined in the `enrol_workdaystudent` plugin. Ensure that the enrollment plugin is correctly set up under **Site administration > Plugins > Enrolments > Workday Student**.

Individual user preferences (such as course creation timing) are stored in Moodle's user preferences tables and do not require global configuration.

## Features

### 1. Course Schedule View
Provides a comprehensive view of an instructor's teaching schedule or a student's class schedule directly from Workday data.
*   Displays course names, numbers, and section details.
*   Shows meeting days, times, and delivery modes, if present.
*   Indicates the current Moodle status of each section (e.g., Created, Pending, Hidden).

### 2. Cross-listing & Splitting
This tool offers advanced management for course sections and Moodle shells:
*   **Cross-listing:** Combine multiple sections—from the same course or different courses—into a single Moodle course shell. This is essential for instructors teaching multiple cohorts who wish to manage them as a single class.
*   **Splitting:** Allows separating sections into distinct shells if they were previously grouped, or creating specific arrangements of shells for a set of sections.
*   The system automatically handles the complex mapping of student and teacher enrollments to the correct combined or split shells.

### 3. Cross-enrollment
Similar to cross-listing, but designed for bridging academic periods.
*   Allows instructors to merge sections from **different academic periods** into a single course shell.
*   Requires that the periods share compatible start and end dates.
*   Useful for combining sections from overlapping terms or modular programs.

### 4. Course Preferences
Instructors can customize the automation behavior for their specific courses:
*   **Create Days Prior:** Specify how many days before the academic period begins that the Moodle course shell should be automatically created.
*   **Enroll Days Prior:** Specify how many days before the start date that students should be enrolled and gain access to the course.
*   **Course Limit:** Set a threshold for course creation.

### 5. Blueprint Shells
Facilitates the creation of template courses:
*   Instructors can request or generate "Blueprint" shells for courses they teach.
*   These shells exist independently of a specific term and serve as a staging area for content development.
*   Content can be easily imported from Blueprint shells into live academic courses.

### 6. Unwanted Sections
*   Allows instructors to opt-out of Moodle course creation for specific sections.
*   Marking a section as "unwanted" prevents the automated system from generating a shell or enrolling students for that specific class.

## Support & Contributing

If you encounter issues or wish to contribute to the development of this plugin:

*   **Support:** Please contact your site administrator or the development team responsible for the Workday Student integration.
*   **Contributing:** Pull requests and issue reports are welcome. Please ensure you adhere to Moodle coding standards.
