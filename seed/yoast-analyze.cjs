const fs=require('fs'),vm=require('vm'),path=require('path');
const root=path.resolve(__dirname,'../../../../');
global.self=global;global.window=global;global.navigator={userAgent:'node'};
global._=require(path.join(root,'wp-includes/js/dist/vendor/lodash.min.js'));global.lodash=global._;
for(const file of ['wp-includes/js/dist/vendor/lodash.min.js','wp-includes/js/dist/hooks.min.js','wp-includes/js/dist/i18n.min.js','wp-content/plugins/wordpress-seo/js/dist/externals/featureFlag.js','wp-content/plugins/wordpress-seo/js/dist/externals/analysis.js','wp-content/plugins/wordpress-seo/js/dist/languages/pt.js']) {
 try { if(!file.includes('lodash.min'))vm.runInThisContext(fs.readFileSync(path.join(root,file),'utf8'),{filename:file}); } catch(e) { console.log(file,e.message);process.exit(1); }
}
const {Paper,SeoAssessor,ContentAssessor}=global.yoast.analysis;
const output=[];
for(const entry of JSON.parse(fs.readFileSync(path.join(__dirname,'seo-analysis-input.json'),'utf8'))) {
 try {
  const paper=new Paper(entry.text,entry);
  const researcher=new global.yoast.Researcher.default(paper);
  const seo=new SeoAssessor(researcher), readability=new ContentAssessor(researcher);
  seo.assess(paper);readability.assess(paper);
  const assessments=seo.getValidResults().map(x=>({id:x.getIdentifier?x.getIdentifier():x._identifier,score:x.getScore(),text:x.getText()}));
  const result={id:entry.id,seo:seo.calculateOverallScore(),readability:readability.calculateOverallScore(),assessments,readabilityAssessments:readability.getValidResults().map(x=>({id:x.getIdentifier?x.getIdentifier():x._identifier,score:x.getScore(),text:x.getText()}))};
  output.push(result);console.log(JSON.stringify(result));
 }catch(e){console.log(entry.id,e.stack);process.exitCode=1;break;}
}
if(!process.exitCode)fs.writeFileSync(path.join(__dirname,'seo-analysis-results.json'),JSON.stringify(output,null,2));
