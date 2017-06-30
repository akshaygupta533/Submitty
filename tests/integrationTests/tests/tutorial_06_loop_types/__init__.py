# Necessary imports. Provides library functions to ease writing tests.
from lib import prebuild, testcase, SUBMITTY_TUTORIAL_DIR

import subprocess
import os
import glob

############################################################################
# COPY THE ASSIGNMENT FROM THE SAMPLE ASSIGNMENTS DIRECTORIES

SAMPLE_ASSIGNMENT_CONFIG = SUBMITTY_TUTORIAL_DIR + "/examples/06_loop_types/config"
SAMPLE_SUBMISSIONS = SUBMITTY_TUTORIAL_DIR + "/examples/06_loop_types/submissions"

@prebuild
def initialize(test):
    try:
        os.mkdir(os.path.join(test.testcase_path, "assignment_config"))
    except OSError:
        pass
    try:
        os.mkdir(os.path.join(test.testcase_path, "data"))
    except OSError:
        pass

    subprocess.call(["cp",
        os.path.join(SAMPLE_ASSIGNMENT_CONFIG, "config.json"),
        os.path.join(test.testcase_path, "assignment_config")])


def cleanup(test):
    subprocess.call(["rm"] + ["-f"] +
            glob.glob(os.path.join(test.testcase_path, "data", "*")))


@testcase
def solution(test):
    cleanup(test)
    subprocess.call(["cp",
                     os.path.join(SAMPLE_SUBMISSIONS, "solution.py"),
                     os.path.join(test.testcase_path, "data")])
    test.run_run()
    test.run_validator()

    print ("TODO:  FIX TUTORIAL 06 LOOP TYPES TEST - SOLUTION")

    #test.diff("results_grade.txt", "results_grade.txt_solution", "-b")
    #test.json_diff("results.json", "results.json_solution")



@testcase
def buggy(test):
    cleanup(test)
    subprocess.call(["cp",
                     os.path.join(SAMPLE_SUBMISSIONS, "buggy.py"),
                     os.path.join(test.testcase_path, "data")])
    test.run_run()
    test.run_validator()

    print ("TODO:  FIX TUTORIAL 06 LOOP TYPES TEST - BUGGY")
    
    #test.diff("results_grade.txt", "results_grade.txt_buggy", "-b")
    #test.json_diff("results.json", "results.json_buggy")

