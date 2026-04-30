const puppeteer = require('puppeteer-core');
const fs = require('fs');

async function scrape() {
  const browser = await puppeteer.connect({
    browserWSEndpoint: 'ws://127.0.0.1:3000?profile=openclaw&timeout=60000'
  });
  const page = await browser.newPage();
  
  // Diverse queries to get a mix of restaurants
  const queries = [
    'best pizza nyc',
    'best sushi nyc',
    'best italian nyc',
    'best thai nyc',
    'best burger nyc',
    'best chinese nyc',
    'best indian nyc',
    'best mexican nyc',
    'best greek nyc',
    'best ramen nyc'
  ];
  
  let restaurants = [];
  
  for (const query of queries) {
    if (restaurants.length >= 50) break;
    
    console.log(`Searching for: ${query}`);
    try {
        await page.goto(`https://www.google.com/search?q=${encodeURIComponent(query)}&tbm=lcl`, { waitUntil: 'networkidle2' });
        
        // Wait for the local results to load
        await page.waitForSelector('.rllt__details', { timeout: 5000 }).catch(() => console.log('No local results found immediately'));

        // Extract basic info from the list
        const links = await page.evaluate(() => {
            const items = Array.from(document.querySelectorAll('.rllt__details'));
            return items.map(div => {
                const nameEl = div.querySelector('.OSrXXb');
                const name = nameEl ? nameEl.innerText : 'Unknown';
                // Try to find a click target
                return { name };
            });
        });

        // Refined strategy: We need to actually visit restaurant sites or reliable aggregators to get specific menu items and hours accurately if Google snippet isn't enough. 
        // However, for speed and autonomy within the tool's constraints, we might just parse the search results if rich enough, or visit the specific maps entry.
        // Let's try a simpler approach: Search for "NYC restaurants menu hours" on a directory site if Google Maps allows, or just generic Google Search results.
        
        // Actually, let's just use Google Search results for "Restaurant Name NYC menu hours" for a few found names.
        // But to get 50, we need a list first.
        
        // Let's use a directory listing from a "Best of" list which is easier to scrape than dynamic maps.
        // Timeout/Blocking risk is high on Google Maps.
    } catch (e) {
        console.error(`Error searching ${query}:`, e.message);
    }
  }
  
  // Alternative: Use a hardcoded list of diverse queries to Yelp or OpenTable or similar if accessible, or just search Google and parse organic results.
  // Let's try searching for "best restaurants nyc" on a site like TimeOut or Eater, extract links, then visit them.
  
  await browser.close();
}

// scrape();
