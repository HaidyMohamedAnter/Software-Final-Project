# conftest.py — place at the project root alongside tests/
# Configures Playwright to run headed or headless based on an env var.
#
# Run headed (you can watch):   HEADED=1 pytest tests/e2e/ -v
# Run headless (CI-friendly):   pytest tests/e2e/ -v

# conftest.py — place at the project root alongside tests/
# Run headed (watch the browser): pytest tests/e2e/test_ui.py -v --headed
# Run headless (fast):            pytest tests/e2e/test_ui.py -v

import os
import pytest


def pytest_addoption(parser):
    parser.addoption(
        "--headed",
        action="store_true",
        default=False,
        help="Run browser tests in headed (visible) mode",
    )


@pytest.fixture(scope="session")
def browser_type_launch_args(browser_type_launch_args, request):
    headed = request.config.getoption("--headed") or os.environ.get("HEADED") == "1"
    return {
        **browser_type_launch_args,
        "headless": not headed,
        "slow_mo": 150,        # slow_mo goes here (launch args), NOT context args
    }