# Selenium Headless Setup Skill

This skill outlines the process of setting up Selenium for headless browser automation on a command-line Linux environment, enabling actions like summarizing, fetching full page source, and getting page titles.

## Prerequisites

Before you begin, ensure you have the following installed:

*   **Python 3:** (e.g., Python 3.8+)
*   **pip:** Python package installer.
*   **venv:** Python's virtual environment module.
*   **wget:** A command-line utility to download files from the web.
*   **unzip:** For extracting zipped files.
*   **Chrome Browser:** The Google Chrome browser needs to be installed.
*   **xvfb:** X virtual framebuffer, to run GUI applications in a headless environment.

## Installation Steps

Follow these steps to set up Selenium for headless operation:

### 1. Install Chrome Browser

```bash
# Add Google's official Chrome repository key
sudo curl -fsSL https://dl-ssl.google.com/linux/linux_signing_key.pub | sudo gpg --dearmor -o /usr/share/keyrings/google-chrome.gpg

# Add the Chrome repository to your sources list
echo 'deb [arch=amd64 signed-by=/usr/share/keyrings/google-chrome.gpg] http://dl.google.com/linux/chrome/deb/ stable main' | sudo tee /etc/apt/sources.list.d/google-chrome.list

# Update package lists
sudo apt-get -y update

# Install Google Chrome Stable
sudo apt-get -y install google-chrome-stable

# Verify Chrome installation
google-chrome --version
```

### 2. Install Chrome WebDriver

ChromeDriver needs to be compatible with your Chrome browser version. `webdriver-manager` is used to automate this, but it's good to know the manual steps.

*Note: For Chrome versions 115+, it's recommended to use the Chrome for Testing (CFT) availability dashboard. However, `webdriver-manager` typically handles version matching automatically.*

### 3. Set up a Python Virtual Environment

It's best practice to isolate your project dependencies.

```bash
# Create a virtual environment named 'venv'
python3 -m venv venv

# Activate the virtual environment
source venv/bin/activate
```

### 4. Install Selenium, WebDriver Manager, and Markdown Parser

Install the necessary Python packages within your activated virtual environment.

```bash
# Install Selenium, webdriver-manager, and markdown
virtualenv/bin/pip install selenium webdriver-manager markdown
```

### 5. Install X Virtual Framebuffer (xvfb)

This is crucial for running browser automation without a physical display.

```bash
# Install xvfb and other necessary display libraries
sudo apt-get update
sudo apt-get install -y libnss3 libnspr4 libgdk-pixbuf-xlib-2.0-0 libgtk-3-0 libgbm1 xvfb
```

### 6. Create and Use the Selenium Skill Script

This script can perform various actions on a webpage: summarize, fetch full source, or get the title. It takes the URL and an optional action as command-line arguments.

Create a file named <code>selenium_summarizer.py</code> (or similar) in your skills directory (e.g., <code>/root/.openclaw/workspace/skills/selenium_summarizer/</code>) with the following content:

<pre><code class="language-python">
import markdown
import os
import sys
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager

def get_webpage_data(url, action='summarize'):
    chrome_options = Options()
    chrome_options.add_argument("--headless=new")  # Use 'new' for modern headless mode
    chrome_options.add_argument("--window-size=1920,1080")
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage') # Often needed in constrained environments

    driver = None
    try:
        service = Service(ChromeDriverManager().install())
        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.get(url)

        if action == 'get_title':
            return driver.title
        elif action == 'fetch_source':
            return driver.page_source
        elif action == 'summarize':
            page_source = driver.page_source
            # Basic text extraction (can be improved with more sophisticated parsing if needed)
            md_content = markdown.markdown(page_source)
            summary = md_content[:1000] + ('...' if len(md_content) > 1000 else '')
            return summary
        else:
            return "Error: Unknown action. Please use 'summarize', 'fetch_source', or 'get_title'"

    except Exception as e:
        return f"An error occurred: {e}"

    finally:
        if driver:
            driver.quit()

if __name__ == '__main__':
    if len(sys.argv) > 2:
        target_url = sys.argv[1]
        action = sys.argv[2]
    elif len(sys.argv) > 1:
        target_url = sys.argv[1]
        action = 'summarize' # Default action if only URL is provided
    else:
        # Fallback for testing if no arguments are provided
        target_url = "https://only-testing-blog.blogspot.com/?m=1" # Default URL for testing
        action = 'summarize'
        print(f"No URL or action provided. Using defaults: URL='{target_url}', Action='{action}'")

    result = get_webpage_data(target_url, action)
    print("\n---\n")
    print(f"{action.replace('_', ' ').title()}:")
    print(result)
        </code></pre>

        <p><strong>To run the script:</strong></p>
        <p>Make sure your virtual environment is activated (<code>source venv/bin/activate</strong>), then run the script using <code>xvfb-run</code>:</p>
        <pre><code class="language-bash">
xvfb-run --auto-servernum --server-num=1 /root/.openclaw/workspace/skills/selenium_summarizer/selenium_summarizer.py [URL] [ACTION]
        </code></pre>
        <p>Example commands:</p>
        <pre><code class="language-bash">
# Summarize a webpage (default action):
xvfb-run --auto-servernum --server-num=1 /root/.openclaw/workspace/skills/selenium_summarizer/selenium_summarizer.py https://example.com

# Fetch the full page source of a URL:
xvfb-run --auto-servernum --server-num=1 /root/.openclaw/workspace/skills/selenium_summarizer/selenium_summarizer.py https://example.com fetch_source

# Get just the title of a webpage:
xvfb-run --auto-servernum --server-num=1 /root/.openclaw/workspace/skills/selenium_summarizer/selenium_summarizer.py https://example.com get_title
        </code></pre>
        <p><strong>Note:</strong> The script can take the URL and an optional action as command-line arguments.</p>

        <h4>Troubleshooting</h4>
        <ul>
            <li><strong><code>NameError: name 'CHROMEDRIVER_PATH' is not defined</code>:</strong> Ensure the <code>webdriver_manager.chrome.ChromeDriverManager().install()</code> line is correctly used, as it handles driver management automatically.</li>
            <li><strong><code>session not created: This version of ChromeDriver only supports Chrome version X</code>:</strong> Make sure your Chrome browser and ChromeDriver versions are compatible. <code>webdriver-manager</code> should resolve this by downloading the correct version.</li>
            <li><strong><code>cannot find Chrome binary</code>:</strong> Verify that Google Chrome is installed correctly and accessible in your system's PATH.</li>
            <li><strong><code>xvfb-run</code> issues:</strong> Ensure <code>xvfb</code> and related libraries (<code>libnss3</code>, <code>libnspr4</code>, <code>libgdk-pixbuf-xlib-2.0-0</code>, <code>libgtk-3-0</code>, <code>libgbm1</code>) are installed.</li>
        </ul>
    </div>

</body>
</html>
