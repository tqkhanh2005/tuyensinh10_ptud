/* Read-only browser checks. Use a dedicated PHP server and private session/report folders. */
const {execFileSync}=require('node:child_process');
const fs=require('node:fs');const path=require('node:path');
const {chromium}=require(process.env.TS10_PLAYWRIGHT || 'playwright');
const [base,sessionDir,reportDir]=process.argv.slice(2);
if(!/^http:\/\/(127\.0\.0\.1|localhost):\d+$/.test(base||'') || !fs.existsSync(sessionDir||'') || !fs.existsSync(reportDir||''))throw Error('Local URL and existing private folders required');
const root=path.resolve(__dirname,'..').toLowerCase()+path.sep;
if([sessionDir,reportDir].some(p=>(path.resolve(p).toLowerCase()+path.sep).startsWith(root)))throw Error('Folders must be outside web root');
const accounts=JSON.parse(execFileSync(process.env.TS10_PHP || 'C:/xampp/php/php.exe',[path.join(__dirname,'browser_session_fixture.php'),sessionDir],{encoding:'utf8'}));
const checks=[];let browser;
(async()=>{try{
    const edge='C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe';
    browser=await chromium.launch({headless:true,...(fs.existsSync(edge)?{executablePath:edge}:{})});
    const pages={public:['/','/auth/login.php'],admin:['/admin/index.php','/admin/audit.php','/admitted/index.php','/statistics/index.php'],teacher:['/teacher/index.php','/teacher/students.php','/teacher/admitted.php','/teacher/statistics.php'],student:['/admission/results.php','/enrollment/confirm.php']};
    for(const width of [390,1440])for(const [role,routes] of Object.entries(pages)){
        const ctx=await browser.newContext({viewport:{width,height:900},deviceScaleFactor:1});
        if(accounts[role])await ctx.addCookies([{name:'PHPSESSID',value:accounts[role],url:base}]);
        for(const route of routes){const page=await ctx.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
            const response=await page.goto(base+route,{waitUntil:'networkidle',timeout:30000});
            const layout=await page.evaluate(()=>({viewport:innerWidth,document:document.documentElement.scrollWidth,body:document.body.scrollWidth,title:document.title}));
            const ok=response.status()===200 && Math.max(layout.document,layout.body)<=width+2 && errors.length===0;
            const filename=role+'_'+width+'_'+route.replace(/[^a-z0-9]+/gi,'_')+'.png';await page.screenshot({path:path.join(reportDir,filename),fullPage:true});
            checks.push({role,route,width,status:response.status(),layout,errors,ok,screenshot:filename});console.log((ok?'PASS: ':'FAIL: ')+role+' '+width+' '+route+' width='+Math.max(layout.document,layout.body));await page.close();
        }await ctx.close();
    }
    fs.writeFileSync(path.join(reportDir,'responsive_handover.json'),JSON.stringify({checks,passed:checks.filter(r=>r.ok).length},null,2));
    if(checks.some(r=>!r.ok))process.exitCode=1;
}finally{if(browser)await browser.close();for(const id of Object.values(accounts)){const file=path.join(sessionDir,'sess_'+id);if(fs.existsSync(file))fs.unlinkSync(file);}}
})().catch(e=>{console.error(e.message);process.exitCode=1;});
