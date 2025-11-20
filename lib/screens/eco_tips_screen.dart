import 'package:flutter/material.dart';

class EcoTipsScreen extends StatefulWidget {
  const EcoTipsScreen({super.key});

  @override
  State<EcoTipsScreen> createState() => _EcoTipsScreenState();
}

class _EcoTipsScreenState extends State<EcoTipsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  final List<Map<String, dynamic>> _recyclingTips = [
    {
      'title': 'Clean Before Recycling',
      'description': 'Always rinse plastic bottles to remove food residue before depositing.',
      'icon': Icons.cleaning_services,
      'color': Colors.blue,
      'impact': 'Increases recycling efficiency by 40%',
    },
    {
      'title': 'Remove Caps & Labels',
      'description': 'Separate bottle caps and labels as they are different plastic types.',
      'icon': Icons.label_off,
      'color': Colors.orange,
      'impact': 'Improves processing quality',
    },
    {
      'title': 'Check Recycling Codes',
      'description': 'Look for numbers 1-7 inside the recycling symbol to identify plastic types.',
      'icon': Icons.numbers,
      'color': Colors.green,
      'impact': 'Ensures proper sorting',
    },
    {
      'title': 'Compress Bottles',
      'description': 'Squeeze out air and compress bottles to save space during transport.',
      'icon': Icons.compress,
      'color': Colors.purple,
      'impact': 'Reduces transport emissions by 25%',
    },
  ];

  final List<Map<String, dynamic>> _environmentalFacts = [
    {
      'title': 'Plastic in Oceans',
      'fact': 'Every minute, a garbage truck full of plastic enters our oceans.',
      'icon': Icons.waves,
      'color': Colors.blue,
      'stat': '8 million tons yearly',
    },
    {
      'title': 'Decomposition Time',
      'fact': 'A plastic bottle takes 450-1000 years to decompose naturally.',
      'icon': Icons.schedule,
      'color': Colors.red,
      'stat': '450-1000 years',
    },
    {
      'title': 'Energy Recovery',
      'fact': 'Recycling one plastic bottle saves enough energy to power a laptop for 6 hours.',
      'icon': Icons.battery_charging_full,
      'color': Colors.green,
      'stat': '6 hours of power',
    },
    {
      'title': 'CO₂ Reduction',
      'fact': 'Recycling plastic reduces CO₂ emissions by 70% compared to making new plastic.',
      'icon': Icons.eco,
      'color': Colors.teal,
      'stat': '70% less CO₂',
    },
  ];

  final List<Map<String, dynamic>> _challenges = [
    {
      'title': 'Weekly Recycler',
      'description': 'Recycle 10 bottles this week',
      'progress': 7,
      'target': 10,
      'reward': '50 points',
      'icon': Icons.emoji_events,
      'color': Colors.amber,
    },
    {
      'title': 'Eco Warrior',
      'description': 'Recycle for 7 consecutive days',
      'progress': 4,
      'target': 7,
      'reward': '100 points',
      'icon': Icons.military_tech,
      'color': Colors.green,
    },
    {
      'title': 'Community Champion',
      'description': 'Be in top 10 this month',
      'progress': 12,
      'target': 10,
      'reward': 'Special badge',
      'icon': Icons.groups,
      'color': Colors.purple,
    },
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Eco Tips'),
        backgroundColor: Colors.green.shade600,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white.withOpacity(0.7),
          tabs: const [
            Tab(icon: Icon(Icons.tips_and_updates), text: 'Tips'),
            Tab(icon: Icon(Icons.public), text: 'Facts'),
            Tab(icon: Icon(Icons.flag), text: 'Challenges'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildTipsTab(),
          _buildFactsTab(),
          _buildChallengesTab(),
        ],
      ),
    );
  }

  Widget _buildTipsTab() {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _recyclingTips.length,
      itemBuilder: (context, index) {
        final tip = _recyclingTips[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 16),
          elevation: 2,
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: tip['color'].withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(
                        tip['icon'],
                        color: tip['color'],
                        size: 28,
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            tip['title'],
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.green.shade50,
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: Colors.green.shade200),
                            ),
                            child: Text(
                              tip['impact'],
                              style: TextStyle(
                                color: Colors.green.shade700,
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Text(
                  tip['description'],
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.grey.shade700,
                    height: 1.4,
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildFactsTab() {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _environmentalFacts.length,
      itemBuilder: (context, index) {
        final fact = _environmentalFacts[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 16),
          elevation: 2,
          child: Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(8),
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  fact['color'].withOpacity(0.1),
                  fact['color'].withOpacity(0.05),
                ],
              ),
            ),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(
                        fact['icon'],
                        color: fact['color'],
                        size: 32,
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Text(
                          fact['title'],
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: fact['color'],
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Text(
                    fact['fact'],
                    style: TextStyle(
                      fontSize: 16,
                      color: Colors.grey.shade700,
                      height: 1.5,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: fact['color'].withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      fact['stat'],
                      style: TextStyle(
                        color: fact['color'],
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _buildChallengesTab() {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _challenges.length,
      itemBuilder: (context, index) {
        final challenge = _challenges[index];
        final progress = challenge['progress'] as int;
        final target = challenge['target'] as int;
        final isCompleted = progress >= target;
        
        return Card(
          margin: const EdgeInsets.only(bottom: 16),
          elevation: 2,
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: challenge['color'].withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(
                        challenge['icon'],
                        color: challenge['color'],
                        size: 28,
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            challenge['title'],
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            challenge['description'],
                            style: TextStyle(
                              color: Colors.grey.shade600,
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (isCompleted)
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: Colors.green.shade100,
                          shape: BoxShape.circle,
                        ),
                        child: Icon(
                          Icons.check,
                          color: Colors.green.shade700,
                          size: 20,
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 16),
                
                // Progress Bar
                Row(
                  children: [
                    Text(
                      'Progress: $progress/$target',
                      style: TextStyle(
                        color: Colors.grey.shade600,
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const Spacer(),
                    Text(
                      '${((progress / target) * 100).clamp(0, 100).toInt()}%',
                      style: TextStyle(
                        color: challenge['color'],
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                LinearProgressIndicator(
                  value: (progress / target).clamp(0.0, 1.0),
                  backgroundColor: Colors.grey.shade200,
                  valueColor: AlwaysStoppedAnimation<Color>(challenge['color']),
                ),
                const SizedBox(height: 12),
                
                // Reward
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: isCompleted ? Colors.green.shade50 : Colors.amber.shade50,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: isCompleted ? Colors.green.shade200 : Colors.amber.shade200,
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        isCompleted ? Icons.check_circle : Icons.card_giftcard,
                        color: isCompleted ? Colors.green.shade700 : Colors.amber.shade700,
                        size: 16,
                      ),
                      const SizedBox(width: 8),
                      Text(
                        isCompleted ? 'Completed!' : 'Reward: ${challenge['reward']}',
                        style: TextStyle(
                          color: isCompleted ? Colors.green.shade700 : Colors.amber.shade700,
                          fontWeight: FontWeight.w600,
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

