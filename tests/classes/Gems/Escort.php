<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Gems_Escort extends GemsEscort {
    public function _initProject() {
        $projectArray = $this->includeFile(APPLICATION_PATH . '/configs/project.example');

        if ($projectArray instanceof \Gems_Project_ProjectSettings) {
            $project = $projectArray;
        } else {
            $project = $this->createProjectClass('Project_ProjectSettings', $projectArray);
        }

        return $project;
    }
}


